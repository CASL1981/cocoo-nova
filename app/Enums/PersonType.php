<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Tipo de persona de un tercero: define qué variante del formato FO-300-01 se diligencia.
 */
enum PersonType: string
{
    use HasLabelOptions;

    case Natural = 'natural';
    case Legal = 'legal';

    public function label(): string
    {
        return match ($this) {
            self::Natural => 'Persona natural',
            self::Legal => 'Persona jurídica',
        };
    }

    /**
     * Tipos de documento válidos para identificar a un tercero de este tipo.
     *
     * @return list<IdentityDocumentType>
     */
    public function documentTypes(): array
    {
        return match ($this) {
            self::Natural => IdentityDocumentType::forPeople(),
            self::Legal => [IdentityDocumentType::Nit, IdentityDocumentType::TemporaryUnion],
        };
    }

    /**
     * Nivel de riesgo según el formato: todo PEP es Alto; si no, natural Medio y jurídica Bajo.
     */
    public function riskLevel(bool $isPep): RiskLevel
    {
        if ($isPep) {
            return RiskLevel::High;
        }

        return $this === self::Natural ? RiskLevel::Medium : RiskLevel::Low;
    }
}
