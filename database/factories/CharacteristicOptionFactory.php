<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Characteristic;
use App\Models\CharacteristicOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CharacteristicOption>
 */
class CharacteristicOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Característica padre a la que pertenece la opción.
            'characteristic_id' => Characteristic::factory(),

            // Valor visible de la opción; rango amplio para no agotar unique() (lección #6).
            'name' => fake()->unique()->words(3, true),

            // Estado inicial de la opción.
            'status' => Status::Active,
        ];
    }
}
