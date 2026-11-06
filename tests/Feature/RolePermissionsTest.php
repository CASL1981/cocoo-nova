<?php

use App\Actions\Permissions\SeedModulePermissions;
use App\Livewire\Roles\RolePermissionsModal;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    (new SeedModulePermissions)->execute('users');
    (new SeedModulePermissions)->execute('roles');
});

it('asigna los permisos seleccionados a un rol', function () {
    $role = Role::factory()->create();

    Livewire::actingAs(actingAsAdmin())
        ->test(RolePermissionsModal::class)
        ->call('openEditable', $role->id)   // ← antes decía 'open'
        ->set('matrix.users.read', true)
        ->set('matrix.users.create', true)
        ->call('save')
        ->assertDispatched('role-permissions-saved')
        ->assertSet('show', false);

    $role->refresh();

    expect($role->hasPermissionTo('users-read'))->toBeTrue()
        ->and($role->hasPermissionTo('users-create'))->toBeTrue()
        ->and($role->hasPermissionTo('users-delete'))->toBeFalse();
});

it('permite ver permisos de solo lectura con el permiso roles-read', function () {
    $viewerRole = Role::factory()->create();
    $viewerRole->syncPermissions(['roles-read']); // sin 'roles-update'

    $viewer = User::factory()->create(['role_id' => $viewerRole->id]);
    $viewer->assignRole($viewerRole);

    $targetRole = Role::factory()->create();
    $targetRole->syncPermissions(['users-read', 'users-create']);

    $component = Livewire::actingAs($viewer)
        ->test(RolePermissionsModal::class)
        ->call('openReadOnly', $targetRole->id)
        ->assertSet('readOnly', true)
        ->assertSet('matrix.users.read', true)
        ->assertSet('matrix.users.create', true)
        ->assertSet('matrix.users.delete', false);

    // Intentar guardar en modo lectura no debe cambiar nada ni lanzar error
    $component->call('save');

    expect($targetRole->fresh()->hasPermissionTo('users-delete'))->toBeFalse();
});

it('bloquea ver permisos sin el permiso roles-read', function () {
    $role = Role::factory()->create();
    $role->syncPermissions([]); // sin ningún permiso

    $user = User::factory()->create(['role_id' => $role->id]);
    $user->assignRole($role);

    $targetRole = Role::factory()->create();

    Livewire::actingAs($user)
        ->test(RolePermissionsModal::class)
        ->call('openReadOnly', $targetRole->id)
        ->assertForbidden();
});

it('crea los permisos del catálogo que aún no existen en la base al guardar', function () {
    $role = Role::factory()->create();
    $admin = actingAsAdmin();

    // Simula un módulo agregado al catálogo después de sembrar la base.
    Permission::where('name', 'like', 'company-settings-%')->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Livewire::actingAs($admin)
        ->test(RolePermissionsModal::class)
        ->call('openEditable', $role->id)
        ->set('matrix.company-settings.read', true)
        ->set('matrix.unknown-module.read', true)
        ->call('save')
        ->assertDispatched('role-permissions-saved');

    expect($role->refresh()->hasPermissionTo('company-settings-read'))->toBeTrue()
        ->and(Permission::where('name', 'unknown-module-read')->exists())->toBeFalse();
});
