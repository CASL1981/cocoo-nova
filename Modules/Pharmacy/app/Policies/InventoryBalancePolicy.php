<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Policies;

use App\Models\User;
use App\Policies\Concerns\HasStandardModuleAbilities;

/**
 * Autorización de la gestión de Oficina Principal (línea de permisos inventory-balances):
 * ver el inventario y la asignación (read), cargar el archivo del ERP (import),
 * asignar cantidades enviadas (process) y corregirlas (reverse).
 */
final class InventoryBalancePolicy
{
    use HasStandardModuleAbilities;

    protected string $module = 'inventory-balances';

    /** Asignar enviado y marcar Pendiente en la pantalla de asignación de stock. */
    public function process(User $actor): bool
    {
        return $this->can($actor, 'process');
    }

    /** Corregir la cantidad enviada de un ítem desde la consulta de ítems. */
    public function reverse(User $actor): bool
    {
        return $this->can($actor, 'reverse');
    }
}
