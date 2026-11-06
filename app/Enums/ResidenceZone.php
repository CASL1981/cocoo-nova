<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Zona del domicilio de una persona natural.
 */
enum ResidenceZone: string
{
    use HasLabelOptions;

    case Urban = 'urban';
    case Rural = 'rural';

    public function label(): string
    {
        return match ($this) {
            self::Urban => 'Urbana',
            self::Rural => 'Rural',
        };
    }
}
