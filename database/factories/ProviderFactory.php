<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\IdentityDocumentType;
use App\Enums\PersonType;
use App\Enums\RiskLevel;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Tercero proveedor (persona jurídica por defecto) sin versión vigente.
 * Para un proveedor aprobado con su formulario usar ProviderVersionFactory::approved().
 *
 * @extends Factory<Provider>
 */
final class ProviderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'person_type' => PersonType::Legal,
            'document_type' => IdentityDocumentType::Nit,
            'identification' => (string) fake()->unique()->numberBetween(800000000, 999999999),
            'name' => mb_strtoupper(fake()->company()).' SAS',
            'risk_level' => RiskLevel::Low,
            'is_pep' => false,
        ];
    }

    public function natural(): static
    {
        return $this->state(fn () => [
            'person_type' => PersonType::Natural,
            'document_type' => IdentityDocumentType::CitizenshipCard,
            'identification' => (string) fake()->unique()->numberBetween(10000000, 99999999),
            'name' => mb_strtoupper(fake()->name()),
            'risk_level' => RiskLevel::Medium,
        ]);
    }
}
