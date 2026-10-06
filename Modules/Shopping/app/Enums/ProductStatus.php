<?php

namespace Modules\Shopping\App\Enums;

/**
 * Estado de un Product dentro del catálogo de Compras.
 * Mismo patrón que UserStatus/ServicePointStatus del core.
 */
enum ProductStatus: string
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
            self::Inactive => 'bg-warning text-dark',   // antes: bg-danger (rojo) — liberado para "eliminado"
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
