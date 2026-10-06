<?php

namespace App\Enums;

/**
 * Estados posibles de un ServicePoint (sede/servicio).
 * Mismo patrón que UserStatus: badgeClass() para la UI y options()
 * para alimentar <x-forms.select> sin construir el arreglo a mano en cada vista.
 */
enum ServicePointStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Inactive => 'Inactivo',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-primary',
            self::Inactive => 'bg-danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
