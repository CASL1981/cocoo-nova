<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class RolePolicy
{
    use ChecksModulePermissions;

    protected string $module = 'roles';

    public function viewAny(User $actor): bool { return $this->can($actor, 'read'); }
    public function create(User $actor): bool { return $this->can($actor, 'create'); }
    public function update(User $actor, Role $role): bool { return $this->can($actor, 'update'); }

    public function view(User $actor, Role $role): bool
    {
        return $this->can($actor, 'read');   // mismo permiso 'roles-read' que ya usa viewAny()
    }

    public function delete(User $actor, Role $role): bool
    {
        return $this->can($actor, 'delete') && $role->assignedUsers()->doesntExist();
    }

    public function managePermissions(User $actor, Role $role): bool
    {
        // Reutilizo 'update' porque asignar permisos es parte de administrar el rol.
        // Si más adelante quieres separarlo (ej. que un rol pueda editar el nombre
        // pero no tocar permisos), lo cambiamos a su propia habilidad.
        return $this->can($actor, 'update');
    }
}
