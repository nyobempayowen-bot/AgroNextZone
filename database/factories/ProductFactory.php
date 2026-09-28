<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'producer_id' => User::factory()->create(['role' => 'producer'])->id,
            'name' => $this->faker->unique()->words(2, true),
            'slug' => $this->faker->unique()->slug(),
            'description' => 'Produit de test.',
            'price' => 1000,
            'unit' => 'kg',
            'stock_quantity' => 100,
            'minimum_order' => 1,
            'is_available' => true,
            'status' => 'published',
            'published_at' => now(),
        ];
    }
}
