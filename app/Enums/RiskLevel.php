<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Nivel de riesgo de un proveedor; lo calcula el sistema (ver PersonType::riskLevel()).
 */
enum RiskLevel: string
{
    use HasLabelOptions;

    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::High => 'Alto',
            self::Medium => 'Medio',
            self::Low => 'Bajo',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::High => 'bg-danger',
            self::Medium => 'bg-warning text-dark',
            self::Low => 'bg-success',
        };
    }
}
