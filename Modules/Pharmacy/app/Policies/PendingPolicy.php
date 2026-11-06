<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Policies;

use App\Models\User;
use App\Policies\Concerns\HasStandardModuleAbilities;
use Illuminate\Database\Eloquent\Model;
use Modules\Pharmacy\App\Enums\PendingStatus;
use Modules\Pharmacy\App\Models\Pending;

/**
 * Autorización de pendientes de farmacia.
 *
 * Un pendiente Terminado no se edita ni elimina: primero debe reabrirse.
 * Editar, eliminar y terminar solo aplican a pendientes con destino en la sede
 * del usuario, salvo Oficina Principal (código 1), que abarca todas las sedes.
 */
final class PendingPolicy
{
    use HasStandardModuleAbilities {
        update as standardUpdate;
        delete as standardDelete;
        duplicate as standardDuplicate;
    }

    protected string $module = 'pendings';

    /**
     * Editar: pendiente activo, permiso de editar y destino en la sede del usuario
     * (Oficina Principal puede editar los de cualquier sede).
     */
    public function update(User $actor, Model $model): bool
    {
        return $model instanceof Pending
            && $this->editable($model)
            && $this->standardUpdate($actor, $model)
            && $this->coversDestination($actor, $model);
    }

    /**
     * Eliminar: misma regla de sede que editar, con el permiso de eliminar.
     */
    public function delete(User $actor, Model $model): bool
    {
        return $model instanceof Pending
            && $this->editable($model)
            && $this->standardDelete($actor, $model)
            && $this->coversDestination($actor, $model);
    }

    /**
     * Duplicar: permiso de crear y destino en la sede del usuario (Oficina Principal
     * duplica los de cualquier sede), igual que la regla de destino al crear.
     */
    public function duplicate(User $actor, Model $model): bool
    {
        return $model instanceof Pending
            && $this->standardDuplicate($actor, $model)
            && $this->coversDestination($actor, $model);
    }

    /**
     * Terminar: lo hace el solicitante o quien tenga el permiso de procesar,
     * y además solo sobre pendientes con destino en la sede del usuario,
     * salvo los usuarios de Oficina Principal, que pueden terminar cualquiera.
     * Un pendiente sin ítems no se puede terminar.
     */
    public function finish(User $actor, Pending $pending): bool
    {
        return $this->editable($pending)
            && ($pending->user_id === $actor->id || $this->can($actor, 'process'))
            && $this->coversDestination($actor, $pending)
            && $pending->items()->exists();
    }

    /**
     * Reabrir un pendiente terminado exige el permiso de reversar.
     */
    public function reopen(User $actor, Pending $pending): bool
    {
        return ! $pending->trashed()
            && $pending->status === PendingStatus::Finished
            && $this->can($actor, 'reverse');
    }

    /**
     * La sede asignada al usuario (users.service_point_id) abarca el destino del pendiente.
     * Un usuario sin sede no puede terminar ninguno.
     */
    private function coversDestination(User $actor, Pending $pending): bool
    {
        if ($actor->service_point_id === null) {
            return false;
        }

        if ($actor->service_point_id === $pending->destination_id) {
            return true;
        }

        return $actor->worksAtHeadquarters();
    }

    private function editable(Pending $pending): bool
    {
        return ! $pending->trashed() && $pending->isActive();
    }
}
