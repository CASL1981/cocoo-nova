<?php

declare(strict_types=1);

namespace Modules\HumanResources\App\Policies;

use App\Models\User;
use App\Policies\Concerns\HasStandardModuleAbilities;
use Illuminate\Database\Eloquent\Model;
use Modules\HumanResources\App\Models\HrCharacteristicOption;

/**
 * Autorización de las opciones (mismo permiso que su característica, a propósito).
 *
 * Una opción solo se modifica si su característica no está eliminada; no se elimina si otras
 * opciones la tienen como jefe inmediato, ni se restaura si su jefe inmediato está eliminado.
 */
final class HrCharacteristicOptionPolicy
{
    use HasStandardModuleAbilities {
        duplicate as private duplicatePermission;
        update as private updatePermission;
        delete as private deletePermission;
        toggle as private togglePermission;
        restore as private restorePermission;
    }

    protected string $module = 'hr-characteristics';

    /** @param HrCharacteristicOption $model */
    public function duplicate(User $actor, Model $model): bool
    {
        return $this->duplicatePermission($actor, $model) && $this->parentIsActive($model);
    }

    /** @param HrCharacteristicOption $model */
    public function update(User $actor, Model $model): bool
    {
        return $this->updatePermission($actor, $model) && $this->parentIsActive($model);
    }

    /** @param HrCharacteristicOption $model */
    public function delete(User $actor, Model $model): bool
    {
        return $this->deletePermission($actor, $model) && $this->parentIsActive($model) && $model->children()->doesntExist();
    }

    /** @param HrCharacteristicOption $model */
    public function toggle(User $actor, Model $model): bool
    {
        return $this->togglePermission($actor, $model) && $this->parentIsActive($model);
    }

    /** @param HrCharacteristicOption $model */
    public function restore(User $actor, Model $model): bool
    {
        return $this->restorePermission($actor, $model) && $this->parentIsActive($model)
            && ($model->parent_id === null || HrCharacteristicOption::query()->whereKey($model->parent_id)->exists());
    }

    /** La relación excluye las características eliminadas (SoftDeletes): no existe si el padre está eliminado. */
    private function parentIsActive(HrCharacteristicOption $option): bool
    {
        return $option->characteristic()->exists();
    }
}
