<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Enums;

/**
 * Estado de una solicitud de producto nuevo.
 *
 * Sin asignar: estado inicial; la farmacia aún puede editarla o eliminarla.
 * Parcial / Cumplido: los calcula la asignación manual de Oficina Principal
 * (enviado >= solicitado → Cumplido; enviado > 0 y menor → Parcial).
 * Rechazado: Oficina Principal decide no codificar el producto; se puede reabrir.
 */
enum NewProductStatus: string
{
    case Unassigned = 'unassigned';
    case Partial = 'partial';
    case Fulfilled = 'fulfilled';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Unassigned => 'Sin asignar',
            self::Partial => 'Parcial',
            self::Fulfilled => 'Cumplido',
            self::Rejected => 'Rechazado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Unassigned => 'bg-secondary',
            self::Partial => 'bg-info',
            self::Fulfilled => 'bg-success',
            self::Rejected => 'bg-danger',
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
     * Solicitudes abiertas: aún les falta envío (gestión, consolidado y bloqueo de duplicados).
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Unassigned, self::Partial];
    }
}
