<?php

declare(strict_types=1);

namespace Modules\Pharmacy\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pharmacy\App\Models\InventoryBalance;

/**
 * @extends Factory<InventoryBalance>
 */
class InventoryBalanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = InventoryBalance::class;

    /**
     * Define the model's default state: ítem no homologado (sin producto ni genérico).
     */
    public function definition(): array
    {
        return [
            'item' => (string) fake()->unique()->numberBetween(100000, 99999999),
            'product_name' => fake()->words(3, true),
            'product_id' => null,
            'generic_code' => null,
            'available_quantity' => fake()->numberBetween(1, 1000),
            'assigned_quantity' => 0,
            'cut_off_date' => now()->toDateString(),
        ];
    }
}
