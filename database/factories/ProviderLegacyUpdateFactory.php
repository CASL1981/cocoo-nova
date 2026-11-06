<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\ProviderLegacyUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderLegacyUpdate>
 */
class ProviderLegacyUpdateFactory extends Factory
{
    public function definition(): array
    {
        $year = fake()->numberBetween(2018, 2025);

        return [
            'provider_id' => Provider::factory(),
            'year' => $year,
            'updated_on' => fake()->dateTimeBetween("{$year}-01-01", "{$year}-12-31")->format('Y-m-d'),
        ];
    }
}
