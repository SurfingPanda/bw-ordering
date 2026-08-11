<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 50, 500),
            'original_price' => null,
            'image_path' => null,
            'features' => [],
            'calorie_info' => [['amount' => $this->faker->numberBetween(100, 800), 'unit' => 'piece']],
            'net_weight' => null,
            'storage_condition' => null,
            'serving_note' => null,
            'is_featured' => false,
            'status' => null,
            'category' => $this->faker->randomElement(['Bread', 'Cakes', 'Pastries']),
            'archived_at' => null,
        ];
    }
}
