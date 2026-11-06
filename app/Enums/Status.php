<?php

namespace App\Enums;

/**
 * Estado genérico Activo/Inactivo, reutilizable en cualquier catálogo
 * nuevo del proyecto que no necesite estados propios más específicos
 * (a diferencia de, por ejemplo, el ciclo de vida de PendingRequest).
 */
enum Status: string
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

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
