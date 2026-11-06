<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Resultado de evaluar una orden de compra frente a la situación del proveedor.
 *
 * Permitida: sin restricciones. FO-300-01 requerido: la orden o el acumulado mensual del proceso
 * supera el tope y el proveedor no tiene formato vigente; la orden se permite con alerta y el
 * proveedor queda marcado. Bloqueada: el formato está vencido o la vinculación no está activa.
 */
enum ProviderPurchaseStatus: string
{
    case Allowed = 'allowed';
    case FormRequired = 'form_required';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Allowed => 'Permitida',
            self::FormRequired => 'Permitida: FO-300-01 requerido',
            self::Blocked => 'Bloqueada',
        };
    }

    /** La orden de compra puede registrarse o aprobarse. */
    public function allowsOrder(): bool
    {
        return $this !== self::Blocked;
    }
}
