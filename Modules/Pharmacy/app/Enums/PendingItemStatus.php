<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Enums;

/**
 * Estado de un ítem de pendiente.
 *
 * Sin revisión: estado inicial al registrarlo.
 * Cumplido / Parcial: los calcula la asignación de Oficina Principal
 * (enviado >= solicitado → Cumplido; enviado > 0 y menor → Parcial).
 * Pendiente: lo marca Oficina Principal.
 * Agotado: se asigna al registrar si el genérico tiene un agotado activo; es definitivo.
 */
enum PendingItemStatus: string
{
    case Unreviewed = 'unreviewed';
    case Fulfilled = 'fulfilled';
    case Partial = 'partial';
    case Pending = 'pending';
    case OutOfStock = 'out_of_stock';

    public function label(): string
    {
        return match ($this) {
            self::Unreviewed => 'Sin revisión',
            self::Fulfilled => 'Cumplido',
            self::Partial => 'Parcial',
            self::Pending => 'Pendiente',
            self::OutOfStock => 'Agotado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Unreviewed => 'bg-secondary',
            self::Fulfilled => 'bg-success',
            self::Partial => 'bg-info',
            self::Pending => 'bg-warning',
            self::OutOfStock => 'bg-danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }

    /**
     * Estados que aparecen en la asignación de stock de Oficina Principal.
     *
     * @return list<self>
     */
    public static function assignable(): array
    {
        return [self::Unreviewed, self::Pending, self::Partial];
    }
}
