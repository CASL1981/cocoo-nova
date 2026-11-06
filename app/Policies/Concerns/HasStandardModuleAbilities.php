<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Habilidades estándar de un CRUD mapeadas a las acciones de PermissionCatalog.
 *
 * La Policy consumidora declara $module y sobrescribe solo las habilidades
 * que tengan reglas de negocio adicionales (p. ej. no eliminar con dependencias).
 */
trait HasStandardModuleAbilities
{
    use ChecksModulePermissions;

    public function viewAny(User $actor): bool
    {
        return $this->can($actor, 'read');
    }

    public function view(User $actor, Model $model): bool
    {
        return $this->can($actor, 'read');
    }

    public function create(User $actor): bool
    {
        return $this->can($actor, 'create');
    }

    /** Duplicar un registro exige el mismo permiso de crear. */
    public function duplicate(User $actor, Model $model): bool
    {
        return $this->can($actor, 'create');
    }

    public function update(User $actor, Model $model): bool
    {
        return $this->can($actor, 'update');
    }

    public function delete(User $actor, Model $model): bool
    {
        return $this->can($actor, 'delete');
    }

    public function toggle(User $actor, Model $model): bool
    {
        return $this->can($actor, 'toggle');
    }

    public function restore(User $actor, Model $model): bool
    {
        return $this->can($actor, 'reverse');
    }

    /** Importación masiva desde Excel/CSV (crea y actualiza registros). */
    public function import(User $actor): bool
    {
        return $this->can($actor, 'import');
    }
}
