<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Situación de un proveedor frente al formato FO-300-01 (se calcula, no se guarda).
 *
 * Sin FO-300-01: proveedor básico (compras menores a 2 SMMLV). En trámite: su primera versión
 * aún no se aprueba. Vigente: tiene versión aprobada dentro del año. Vencido: pasó la fecha de
 * la actualización anual; no puede recibir órdenes de compra hasta actualizarlo.
 */
enum ProviderFormStatus: string
{
    use HasLabelOptions;

    case None = 'none';
    case InProgress = 'in_progress';
    case Current = 'current';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Sin FO-300-01',
            self::InProgress => 'En trámite',
            self::Current => 'Vigente',
            self::Expired => 'Vencido',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::None => 'bg-light text-dark border',
            self::InProgress => 'bg-info',
            self::Current => 'bg-success',
            self::Expired => 'bg-danger',
        };
    }
}
