<?php

use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;


it('filtra usuarios por búsqueda', function () {
    User::factory()->create(['name' => 'Demo Admin']);
    User::factory()->create(['name' => 'John User']);

    Livewire::actingAs(actingAsAdmin())
        ->test('pages::user-index')
        ->set('filterName', 'Demo')
        ->assertSee('Demo Admin')
        ->assertDontSee('John User');
});

it('valida y crea un usuario', function () {
    $role = Role::factory()->create();

    $component = Livewire::actingAs(actingAsAdmin())
        ->test(\App\Livewire\Users\UserFormModal::class)
        ->call('openCreate');

    // 'form.password' y 'form.password_confirmation' salen de la lista:
    // ya no son campos del formulario, la clave se genera sola en SaveUser.
    $component->call('save')->assertHasErrors(['form.name', 'form.email', 'form.role_id']);

    $component->set('form.identification', '1020304050')
        ->set('form.name', 'Ana')
        ->set('form.lastname', 'Ruiz')
        ->set('form.email', 'ana@example.com')
        ->set('form.role_id', (string) $role->id)
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'ana@example.com')->first();

    expect($user->role_id)->toBe($role->id)
        ->and($user->must_change_password)->toBeTrue();   // confirma el nuevo comportamiento esperado
});

it('exige seleccionar un rol', function () {
    Livewire::actingAs(actingAsAdmin())
        ->test(\App\Livewire\Users\UserFormModal::class)
        ->call('openCreate')
        ->set('form.name', 'Ana')
        ->set('form.identification', '1020304050')
        ->set('form.lastname', 'Ruiz')
        ->set('form.email', 'ana@example.com')
        ->set('form.password', 'Str0ng-Passw0rd!')
        ->set('form.password_confirmation', 'Str0ng-Passw0rd!')
        ->call('save')
        ->assertHasErrors(['form.role_id']);
});

it('sincroniza el rol seleccionado con laravel-permission', function () {
    $role = Role::factory()->create(['name' => 'Administrador']);

    Livewire::actingAs(actingAsAdmin())
        ->test(\App\Livewire\Users\UserFormModal::class)
        ->call('openCreate')
        ->set('form.identification', '1020304050')
        ->set('form.name', 'Ana')
        ->set('form.lastname', 'Ruiz')
        ->set('form.email', 'ana@example.com')
        ->set('form.role_id', (string) $role->id)
        ->set('form.password', 'Str0ng-Passw0rd!')
        ->set('form.password_confirmation', 'Str0ng-Passw0rd!')
        ->call('save');

    $user = User::where('email', 'ana@example.com')->first();

    expect($user->role_id)->toBe($role->id)
        ->and($user->hasRole('Administrador'))->toBeTrue();
});

it('bloquea la creación de usuarios sin el permiso users-create', function () {
    $role = Role::factory()->create();
    $role->syncPermissions(['users-read']); // tiene lectura, pero no 'users-create'

    $limitedUser = User::factory()->create(['role_id' => $role->id]);
    $limitedUser->assignRole($role);

    Livewire::actingAs($limitedUser)
        ->test(\App\Livewire\Users\UserFormModal::class)
        ->call('openCreate')
        ->assertForbidden();
});

it('permite abrir el modal de creación con el permiso users-create', function () {
    $role = Role::factory()->create();
    $role->syncPermissions(['users-create']);

    $user = User::factory()->create(['role_id' => $role->id]);
    $user->assignRole($role);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Users\UserFormModal::class)
        ->call('openCreate')
        ->assertOk()
        ->assertSet('show', true);
});

it('exporta el listado de usuarios a excel', function () {
    User::factory()->count(2)->create();

    Livewire::actingAs(actingAsAdmin())
        ->test('pages::user-index')
        ->call('export', 'xlsx')
        ->assertFileDownloaded();
});
