<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Naturaleza del capital de una persona jurídica.
 */
enum CompanyType: string
{
    use HasLabelOptions;

    case Public = 'public';
    case Private = 'private';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Pública',
            self::Private => 'Privada',
            self::Mixed => 'Mixta',
        };
    }
}
