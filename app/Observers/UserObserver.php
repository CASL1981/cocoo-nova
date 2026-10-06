<?php

namespace App\Observers;

use App\Models\Role;
use App\Models\User;

class UserObserver
{
    public function saved(User $user): void
    {
        // Solo actuar si role_id cambió, o si es un registro recién creado con role_id ya asignado
        if (! $user->wasChanged('role_id') && ! ($user->wasRecentlyCreated && $user->role_id)) {
            return;
        }

        $role = $user->role_id ? Role::find($user->role_id) : null;

        $user->syncRoles($role ? [$role->name] : []);
    }
}
