<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Pending => 'Pendiente',
            self::Inactive => 'Inactivo',
        };
    }

    // Colores de la pantalla: morado (primary), naranja (warning), rojo (danger)
    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-primary',
            self::Pending => 'bg-warning',
            self::Inactive => 'bg-danger',
        };
    }

    /**
     * Summary of options
     * @return string[]
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
