<?php

declare(strict_types=1);

namespace Modules\Pharmacy\Database\Factories;

use App\Models\ServicePoint;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;
use Modules\Shopping\App\Models\Product;

/**
 * @extends Factory<PendingItem>
 */
class PendingItemFactory extends Factory
{
    protected $model = PendingItem::class;

    public function definition(): array
    {
        return [
            'pending_id' => Pending::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(3, true),
            'generic_code' => (string) fake()->numberBetween(100000, 99999999),
            'generic_name' => fake()->words(2, true),
            'brand' => fake()->optional()->company(),
            // El destino por defecto es el del encabezado (código de su sede).
            'destination_code' => fn (array $attributes) => Pending::with('destination')->find($attributes['pending_id'])?->destination?->code
                ?? ServicePoint::factory()->create()->code,
            'quantity' => fake()->numberBetween(1, 500),
            'status' => PendingItemStatus::Unreviewed,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state([
            'status' => PendingItemStatus::OutOfStock,
            'circular_number' => (string) fake()->numberBetween(1000, 999999),
        ]);
    }
}
