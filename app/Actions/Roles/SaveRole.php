<?php

namespace App\Actions\Roles;

use App\Models\Role;

class SaveRole
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function execute(array $data, ?Role $role = null): Role
    {
        $role ??= new Role();
        $role->fill($data)->save();

        return $role;
    }
}
