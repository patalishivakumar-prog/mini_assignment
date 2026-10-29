<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(2, true),
            'code' => $this->faker->unique()->bothify('PRD-####'),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'tax_percentage' => 18.00,
            'stock_quantity' => $this->faker->numberBetween(0, 100),
        ];
    }
}