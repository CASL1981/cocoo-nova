<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Policies;

use App\Models\User;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;

/**
 * Autorización de ítems de pendientes.
 *
 * Los ítems usan los permisos de `pendings`: agregar, editar o eliminar un ítem es
 * editar el pendiente, así que se delega en PendingPolicy::update (pendiente activo,
 * permiso pendings-update y destino en la sede del usuario u Oficina Principal).
 * Un ítem Agotado es definitivo: no se edita, solo se elimina.
 */
final class PendingItemPolicy
{
    /** Consulta de ítems (todas las sedes y estados): permiso de lectura de pendientes. */
    public function viewAny(User $actor): bool
    {
        return $actor->can('pendings-read');
    }

    /** Ver un ítem: permiso de lectura de pendientes. */
    public function view(User $actor, PendingItem $item): bool
    {
        return $actor->can('pendings-read');
    }

    /** Agregar ítems al pendiente indicado. */
    public function create(User $actor, Pending $pending): bool
    {
        return $actor->can('update', $pending);
    }

    public function update(User $actor, PendingItem $item): bool
    {
        return ! $item->isOutOfStock() && $this->delete($actor, $item);
    }

    public function delete(User $actor, PendingItem $item): bool
    {
        $pending = $item->loadMissing('pending')->pending;

        return $pending !== null && $actor->can('update', $pending);
    }
}
