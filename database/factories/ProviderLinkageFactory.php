<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderScope;
use App\Models\Provider;
use App\Models\ProviderLinkage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Vinculación comercial pendiente de un proveedor.
 *
 * @extends Factory<ProviderLinkage>
 */
final class ProviderLinkageFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'scope' => ProviderScope::Commercial,
            'status' => ProviderLinkageStatus::Pending,
        ];
    }

    public function scope(ProviderScope $scope): static
    {
        return $this->state(fn () => ['scope' => $scope]);
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => ProviderLinkageStatus::Active, 'approved_at' => now()]);
    }
}
