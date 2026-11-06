<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Tipo de solicitud del formato FO-300-01: la primera versión es una vinculación; las siguientes, actualizaciones.
 */
enum ProviderRequestType: string
{
    use HasLabelOptions;

    case Linkage = 'linkage';
    case Update = 'update';

    public function label(): string
    {
        return match ($this) {
            self::Linkage => 'Vinculación',
            self::Update => 'Actualización de datos',
        };
    }
}
