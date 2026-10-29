<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_api_returns_products_below_threshold()
    {
        Product::create([
            'name' => 'Low Stock Product',
            'code' => 'LOW-001',
            'price' => 100,
            'tax_percentage' => 18,
            'stock_quantity' => 3,
        ]);

        Product::create([
            'name' => 'Available Product',
            'code' => 'LOW-002',
            'price' => 100,
            'tax_percentage' => 18,
            'stock_quantity' => 8,
        ]);

        $response = $this->getJson(
            '/api/products/low-stock?threshold=5'
        );

        $response->assertOk()
            ->assertJsonPath('threshold', 5)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('products.0.code', 'LOW-001');
    }

    public function test_low_stock_api_rejects_invalid_threshold()
    {
        $response = $this->getJson(
            '/api/products/low-stock?threshold=-1'
        );

        $response->assertStatus(422);
    }

    public function test_customer_order_history_requires_email()
    {
        $response = $this->getJson('/api/customers/orders');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
