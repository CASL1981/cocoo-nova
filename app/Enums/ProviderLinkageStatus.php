<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Estado de la vinculación de un proveedor con un proceso.
 *
 * Pendiente: su versión del FO-300-01 aún no se aprueba. Activa/Inactiva: aprobada; cada
 * proceso la activa o inactiva sin afectar al otro. Rechazada: nunca llegó a aprobarse.
 */
enum ProviderLinkageStatus: string
{
    use HasLabelOptions;

    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Active => 'Activa',
            self::Inactive => 'Inactiva',
            self::Rejected => 'Rechazada',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-secondary',
            self::Active => 'bg-success',
            self::Inactive => 'bg-warning text-dark',
            self::Rejected => 'bg-danger',
        };
    }
}
