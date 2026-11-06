<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GeographicLocation;
use App\Models\User;
use App\Policies\Concerns\HasStandardModuleAbilities;
use Illuminate\Database\Eloquent\Model;

/**
 * Autorización de Configuración → Ubicación geográfica.
 *
 * Además del permiso: no se elimina una ubicación en uso o con hijas activas, ni se restaura
 * una cuyo padre está eliminado.
 */
final class GeographicLocationPolicy
{
    use HasStandardModuleAbilities {
        delete as private deletePermission;
        restore as private restorePermission;
    }

    protected string $module = 'geographic-locations';

    /** @param GeographicLocation $model */
    public function delete(User $actor, Model $model): bool
    {
        return $this->deletePermission($actor, $model) && $model->isDeletable();
    }

    /** @param GeographicLocation $model */
    public function restore(User $actor, Model $model): bool
    {
        return $this->restorePermission($actor, $model)
            && ($model->parent_id === null || $model->parent()->exists());
    }
}
