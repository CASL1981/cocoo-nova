<?php

declare(strict_types=1);

namespace Modules\Pharmacy\Database\Factories;

use App\Models\ServicePoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pharmacy\App\Enums\NewProductStatus;
use Modules\Pharmacy\App\Models\NewProduct;
use Modules\Shopping\App\Models\Product;

/**
 * @extends Factory<NewProduct>
 */
class NewProductFactory extends Factory
{
    protected $model = NewProduct::class;

    public function definition(): array
    {
        return [
            // Rango amplio para no repetir nombres (el bloqueo de duplicados compara el nombre).
            'product_name' => 'PRODUCTO '.fake()->unique()->numberBetween(100000, 99999999),
            'brand' => fake()->optional()->company(),
            'quantity' => fake()->numberBetween(1, 500),
            'destination_code' => fn () => ServicePoint::factory()->create()->code,
            'user_id' => User::factory(),
            'status' => NewProductStatus::Unassigned,
        ];
    }

    /** Asociada a un ítem del catálogo (lista para asignar unidades). */
    public function linked(?Product $product = null): static
    {
        return $this->state(fn () => ['product_id' => $product !== null ? $product->id : Product::factory()]);
    }

    public function fulfilled(): static
    {
        return $this->linked()->state(fn (array $attributes) => [
            'send_quantity' => $attributes['quantity'],
            'date_send' => now()->toDateString(),
            'status' => NewProductStatus::Fulfilled,
        ]);
    }
}
