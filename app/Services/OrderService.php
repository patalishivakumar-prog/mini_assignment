<?php

namespace App\Services;

use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function createOrder(array $data)
    {
        $order = DB::transaction(function () use ($data) {
            $customer = Customer::firstOrCreate(
                ['email' => $data['customer']['email']],
                ['name' => $data['customer']['name']]
            );

            $items = collect($data['items']);

            $productIds = $items->pluck('product_id')
                ->sort()
                ->values();

            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotalCents = 0;
            $taxCents = 0;
            $orderLines = [];

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];

                if (!$product || $product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => [
                            'Insufficient stock for product ID '
                            . $item['product_id'],
                        ],
                    ]);
                }

                $unitPriceCents = (int) round(
                    (float) $product->price * 100
                );

                $lineSubtotalCents = $unitPriceCents * $quantity;

                $taxBasisPoints = (int) round(
                    (float) $product->tax_percentage * 100
                );

                $lineTaxCents = (int) round(
                    $lineSubtotalCents * $taxBasisPoints / 10000
                );

                $subtotalCents += $lineSubtotalCents;
                $taxCents += $lineTaxCents;

                $orderLines[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPriceCents / 100,
                    'tax_percentage' => $product->tax_percentage,
                    'line_subtotal' => $lineSubtotalCents / 100,
                    'line_tax' => $lineTaxCents / 100,
                    'line_total' => (
                        $lineSubtotalCents + $lineTaxCents
                    ) / 100,
                ];
            }

            $order = Order::create([
                'customer_id' => $customer->id,
                'subtotal' => $subtotalCents / 100,
                'tax_amount' => $taxCents / 100,
                'grand_total' => ($subtotalCents + $taxCents) / 100,
            ]);

            foreach ($orderLines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'tax_percentage' => $line['tax_percentage'],
                    'line_subtotal' => $line['line_subtotal'],
                    'line_tax' => $line['line_tax'],
                    'line_total' => $line['line_total'],
                ]);

                $line['product']->decrement(
                    'stock_quantity',
                    $line['quantity']
                );
            }

            return $order;
        });

        // Dispatch only after the order transaction succeeds.
        SendOrderConfirmation::dispatch($order->id);

        return $order;
    }

    // public function createOrder(array $data)
    // {
    //     return DB::transaction(function () use ($data) {

    //         $customer = Customer::firstOrCreate(
    //             ['email' => $data['customer']['email']],
    //             ['name' => $data['customer']['name']]
    //         );

    //         $items = collect($data['items']);

    //         $productIds = $items
    //             ->pluck('product_id')
    //             ->sort()
    //             ->values();

    //         // Lock products in a consistent order.
    //         $products = Product::whereIn('id', $productIds)
    //             ->orderBy('id')
    //             ->lockForUpdate()
    //             ->get()
    //             ->keyBy('id');

    //         $subtotalCents = 0;
    //         $taxCents = 0;
    //         $orderLines = [];

    //         foreach ($items as $item) {
    //             $product = $products->get($item['product_id']);
    //             $quantity = (int) $item['quantity'];

    //             if (!$product ||
    //                 $product->stock_quantity < $quantity) {
    //                 throw ValidationException::withMessages([
    //                     'items' => [
    //                         'Insufficient stock for product ID '
    //                         . $item['product_id'],
    //                     ],
    //                 ]);
    //             }

    //             // Convert the unit price to integer paise/cents.
    //             $unitPriceCents = (int) round(
    //                 (float) $product->price * 100
    //             );

    //             $lineSubtotalCents =
    //                 $unitPriceCents * $quantity;

    //             // Tax percentage is stored with two decimal places.
    //             $taxBasisPoints = (int) round(
    //                 (float) $product->tax_percentage * 100
    //             );

    //             $lineTaxCents = (int) round(
    //                 $lineSubtotalCents * $taxBasisPoints / 10000
    //             );

    //             $subtotalCents += $lineSubtotalCents;
    //             $taxCents += $lineTaxCents;

    //             $orderLines[] = [
    //                 'product' => $product,
    //                 'quantity' => $quantity,
    //                 'unit_price' => $unitPriceCents / 100,
    //                 'tax_percentage' => $product->tax_percentage,
    //                 'line_subtotal' => $lineSubtotalCents / 100,
    //                 'line_tax' => $lineTaxCents / 100,
    //                 'line_total' =>
    //                     ($lineSubtotalCents + $lineTaxCents) / 100,
    //             ];
    //         }

    //         $order = Order::create([
    //             'customer_id' => $customer->id,
    //             'subtotal' => $subtotalCents / 100,
    //             'tax_amount' => $taxCents / 100,
    //             'grand_total' =>
    //                 ($subtotalCents + $taxCents) / 100,
    //         ]);

    //         foreach ($orderLines as $line) {
    //             $order->items()->create([
    //                 'product_id' => $line['product']->id,
    //                 'product_name' => $line['product']->name,
    //                 'quantity' => $line['quantity'],
    //                 'unit_price' => $line['unit_price'],
    //                 'tax_percentage' => $line['tax_percentage'],
    //                 'line_subtotal' => $line['line_subtotal'],
    //                 'line_tax' => $line['line_tax'],
    //                 'line_total' => $line['line_total'],
    //             ]);

    //             $line['product']->decrement(
    //                 'stock_quantity',
    //                 $line['quantity']
    //             );
    //         }

    //         return $order;
    //     });
    // }
}