<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Tipo de proveedor según el proceso que lo gestiona.
 *
 * Cada tipo tiene su propia línea de permisos y su pantalla en el módulo del proceso:
 * Administrativo (papelería, cafetería y otros) en Administrative y Comercial
 * (medicamentos y dispositivos médicos) en Shopping. Aprueba el director del proceso indicado.
 */
enum ProviderScope: string
{
    use HasLabelOptions;

    case Administrative = 'administrative';
    case Commercial = 'commercial';

    public function label(): string
    {
        return match ($this) {
            self::Administrative => 'Administrativo',
            self::Commercial => 'Comercial',
        };
    }

    /** Slug de permisos de PermissionCatalog; también es el prefijo de los nombres de sus rutas. */
    public function module(): string
    {
        return match ($this) {
            self::Administrative => 'administrative-providers',
            self::Commercial => 'commercial-providers',
        };
    }

    /**
     * Todo proveedor del proceso diligencia el FO-300-01. Los comerciales siempre lo tienen; los
     * administrativos solo cuando una orden o el acumulado mensual supera el tope (2 SMMLV).
     */
    public function requiresForm(): bool
    {
        return $this === self::Commercial;
    }

    /** Nombre del proceso (tabla processes) cuyo director aprueba la vinculación. */
    public function processName(): string
    {
        return match ($this) {
            self::Administrative => 'Administrativo',
            self::Commercial => 'Comercial',
        };
    }
}
