<?php

use App\Models\Role;
use App\Models\User;

it('bloquea el acceso directo a /roles sin el permiso roles-read', function () {
    $role = Role::factory()->create();
    $role->syncPermissions([]); // sin permisos

    $user = User::factory()->create(['role_id' => $role->id]);
    $user->assignRole($role);

    $this->actingAs($user)->get(route('roles.index'))->assertForbidden();
});

it('permite el acceso a /roles con el permiso roles-read', function () {
    $role = Role::factory()->create();
    $role->syncPermissions(['roles-read']);

    $user = User::factory()->create(['role_id' => $role->id]);
    $user->assignRole($role);

    $this->actingAs($user)->get(route('roles.index'))->assertOk();
});
