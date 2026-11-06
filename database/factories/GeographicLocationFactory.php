<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GeographicLevel;
use App\Models\GeographicLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Por defecto crea un país; department() y city() crean su padre si no se indica.
 *
 * @extends Factory<GeographicLocation>
 */
final class GeographicLocationFactory extends Factory
{
    protected $model = GeographicLocation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level' => GeographicLevel::Country,
            'parent_id' => null,
            'code' => 'T'.fake()->unique()->numerify('######'),
            'name' => fake()->unique()->country(),
            'population' => null,
            'status' => 'active',
        ];
    }

    public function department(?GeographicLocation $country = null): self
    {
        return $this->state(fn () => [
            'level' => GeographicLevel::Department,
            'parent_id' => $country->id ?? GeographicLocation::factory(),
            'name' => 'Departamento '.fake()->unique()->numerify('####'),
        ]);
    }

    public function city(?GeographicLocation $department = null): self
    {
        return $this->state(fn () => [
            'level' => GeographicLevel::City,
            'parent_id' => $department->id ?? GeographicLocation::factory()->department(),
            'name' => fake()->unique()->city(),
        ]);
    }
}
