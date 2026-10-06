<?php

namespace Database\Factories;

use App\Enums\ServicePointStatus;
use App\Enums\ServicePointType;
use App\Models\ServicePoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePoint>
 */
class ServicePointFactory extends Factory
{
    protected $model = ServicePoint::class;

    public function definition(): array
    {
        return [
            // sequence() + un sufijo aleatorio corto: nunca se agota, a diferencia
            // de elegir entre una lista fija de 6 códigos reales con unique().
            'code' => (string) fake()->unique()->numberBetween(9000, 99999),
            'name' => fake()->company().' Punto de Servicio',
            'type' => ServicePointType::Pharmacy,
            'address' => fake()->address(),
            'phone' => fake()->numerify('3#########'),
            'minimum' => null,
            'maximum' => null,
            'status' => ServicePointStatus::Active,
        ];
    }
}
