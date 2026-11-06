<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProviderRequestType;
use App\Enums\ProviderVersionStatus;
use App\Enums\RiskLevel;
use App\Models\Provider;
use App\Models\ProviderVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Versión del formato FO-300-01 en Borrador con los datos mínimos del tercero y las
 * declaraciones obligatorias aceptadas (lista para enviar a verificación).
 *
 * @extends Factory<ProviderVersion>
 */
final class ProviderVersionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'number' => 1,
            'request_type' => ProviderRequestType::Linkage,
            'status' => ProviderVersionStatus::Draft,
            'person_type' => fn (array $attributes) => self::provider($attributes)->person_type,
            'document_type' => fn (array $attributes) => self::provider($attributes)->document_type,
            'identification' => fn (array $attributes) => self::provider($attributes)->identification,
            'name' => fn (array $attributes) => self::provider($attributes)->name,
            'risk_level' => fn (array $attributes) => self::provider($attributes)->risk_level,
            'is_pep' => false,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('300#######'),
            'address' => fake()->streetAddress(),
            'monthly_income' => fake()->numberBetween(1000000, 90000000),
            'accepts_truthfulness' => true,
            'accepts_data_processing' => true,
            'accepts_information_received' => true,
            'accepts_funds_origin' => true,
        ];
    }

    public function status(ProviderVersionStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    /** Versión aprobada que además queda como vigente del proveedor. */
    public function approved(): static
    {
        return $this->state(fn () => ['status' => ProviderVersionStatus::Approved, 'approved_at' => now()])
            ->afterCreating(function (ProviderVersion $version): void {
                $version->provider->update([
                    'current_version_id' => $version->id,
                    'next_update_on' => now()->addYear()->toDateString(),
                ]);
            });
    }

    public function pep(): static
    {
        return $this->state(fn () => ['declares_pep' => true, 'public_office' => 'Alcalde', 'pep_starts_on' => '2024-01-01', 'is_pep' => true,
            'risk_level' => RiskLevel::High]);
    }

    /**
     * Proveedor de la versión, para copiar su identificación.
     *
     * @param  array<string, mixed>  $attributes
     */
    private static function provider(array $attributes): Provider
    {
        return Provider::query()->whereKey($attributes['provider_id'])->firstOrFail();
    }
}
