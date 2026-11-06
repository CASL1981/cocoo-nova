<?php

declare(strict_types=1);

namespace Modules\Pharmacy\Database\Factories;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pharmacy\App\Models\StockOut;

class StockOutFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = StockOut::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'generic_name' => fake()->words(2, true),
            'product_name' => fake()->words(3, true),
            'brand' => fake()->company(),
            'classification' => fake()->words(2, true),
            'circular_number' => (string) fake()->numberBetween(1000, 999999),
            'circular_date' => fake()->dateTimeBetween('-1 year'),
            // Rango amplio para no agotar unique() en la suite (lección #6).
            'generic_code' => (string) fake()->unique()->numberBetween(100000, 99999999),
            'status' => Status::Active,
        ];
    }
}
