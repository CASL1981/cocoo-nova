<?php

namespace Modules\Shopping\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Models\Product;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'item' => 'PRD-'.fake()->unique()->numberBetween(10000, 99999),
            'description' => fake()->words(3, true),
            'brand' => fake()->company(),
            'measure_unit' => fake()->randomElement(['Caja', 'Frasco', 'Tableta', 'Unidad']),
            'generic_name' => fake()->word(),
            'generic_code' => fake()->unique()->numberBetween(1000, 9999),
            'status' => ProductStatus::Active,
        ];
    }
}

