<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_be_created_successfully()
    {
        $product = Product::create([
            'name' => 'Test Product',
            'code' => 'TEST-001',
            'price' => 100.00,
            'tax_percentage' => 18.00,
            'stock_quantity' => 10,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
            ],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', 200)
            ->assertJsonPath('data.tax_amount', 36)
            ->assertJsonPath('data.grand_total', 236);

        $this->assertSame(
            8,
            (int) $product->fresh()->stock_quantity
        );
    }

    public function test_order_is_rejected_when_stock_is_insufficient()
    {
        $product = Product::create([
            'name' => 'Limited Product',
            'code' => 'TEST-002',
            'price' => 100.00,
            'tax_percentage' => 18.00,
            'stock_quantity' => 1,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
            ],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(422);

        $this->assertSame(
            1,
            (int) $product->fresh()->stock_quantity
        );

        $this->assertSame(0, Order::count());
    }

    
public function test_stock_cannot_be_sold_twice_when_only_one_unit_remains()
{
    $product = Product::create([
        'name' => 'Last Unit Product',
        'code' => 'TEST-003',
        'price' => 100.00,
        'tax_percentage' => 18.00,
        'stock_quantity' => 1,
    ]);

    $payload = [
        'customer' => [
            'name' => 'Stock Test Customer',
            'email' => 'stocktest@example.com',
        ],
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
    ];

    $firstResponse = $this->postJson('/api/orders', $payload);
    $firstResponse->assertCreated();

    $secondResponse = $this->postJson('/api/orders', $payload);
    $secondResponse->assertStatus(422);

    $this->assertSame(
        0,
        (int) $product->fresh()->stock_quantity
    );

    $this->assertSame(1, Order::count());
}

}
