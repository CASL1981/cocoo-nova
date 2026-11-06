<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Process;
use App\Models\ProcessSubstitution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessSubstitution>
 */
final class ProcessSubstitutionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $startsOn = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'process_id' => Process::factory(),
            'substitute_id' => User::factory(),
            'starts_on' => $startsOn->format('Y-m-d'),
            'ends_on' => (clone $startsOn)->modify('+15 days')->format('Y-m-d'),
            'observations' => fake()->optional()->sentence(),
        ];
    }

    /** Asignación permanente: sin fecha de fin. */
    public function permanent(): static
    {
        return $this->state(fn () => ['ends_on' => null]);
    }
}
