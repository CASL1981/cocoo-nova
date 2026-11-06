<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Process;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Process>
 */
final class ProcessFactory extends Factory
{
    /**
     * Nombres ficticios con rango amplio para no chocar con el índice único;
     * los procesos reales se siembran en ProcessSeeder.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Proceso '.fake()->unique()->numberBetween(1, 999999),
            'director_id' => null,
            'status' => Status::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => Status::Inactive]);
    }
}
