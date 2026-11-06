<?php

declare(strict_types=1);

namespace Modules\Pharmacy\Database\Factories;

use App\Models\ServicePoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Pharmacy\App\Enums\PendingStatus;
use Modules\Pharmacy\App\Enums\PendingType;
use Modules\Pharmacy\App\Models\Pending;

/**
 * @extends Factory<Pending>
 */
class PendingFactory extends Factory
{
    protected $model = Pending::class;

    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(PendingType::cases()),
            'category' => fake()->words(2, true),
            'destination_id' => ServicePoint::factory(),
            'reason' => fake()->sentence(4),
            'duration' => fake()->randomElement(['1 mes', '3 meses', 'Indefinido']),
            'observations' => fake()->optional()->paragraph(),
            'eps' => fake()->optional()->company(),
            'contracting_modality' => null,
            'invoicing_method' => null,
            'user_id' => User::factory(),
            'manager' => fake()->optional()->name(),
            'status' => PendingStatus::Active,
        ];
    }

    public function finished(): static
    {
        return $this->state(['status' => PendingStatus::Finished]);
    }
}
