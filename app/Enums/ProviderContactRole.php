<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Cargos fijos de la sección "Contactos" del formato FO-300-01.
 */
enum ProviderContactRole: string
{
    use HasLabelOptions;

    case SalesManager = 'sales_manager';
    case SalesRepresentative = 'sales_representative';
    case Billing = 'billing';
    case Collections = 'collections';
    case TechnicalDirector = 'technical_director';

    public function label(): string
    {
        return match ($this) {
            self::SalesManager => 'Jefe comercial o jefe de ventas',
            self::SalesRepresentative => 'Representante de ventas',
            self::Billing => 'Facturación',
            self::Collections => 'Cartera',
            self::TechnicalDirector => 'Dirección técnica',
        };
    }
}
