<?php

use App\Livewire\Users\UserFormModal;
use App\Models\Process;
use App\Models\Role;
use App\Models\ServicePoint;
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
        ->test(UserFormModal::class)
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
        ->test(UserFormModal::class)
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
        ->test(UserFormModal::class)
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
        ->test(UserFormModal::class)
        ->call('openCreate')
        ->assertForbidden();
});

it('permite abrir el modal de creación con el permiso users-create', function () {
    $role = Role::factory()->create();
    $role->syncPermissions(['users-create']);

    $user = User::factory()->create(['role_id' => $role->id]);
    $user->assignRole($role);

    Livewire::actingAs($user)
        ->test(UserFormModal::class)
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

it('exige proceso y sede al habilitar la solicitud de suministros', function () {
    Livewire::actingAs(actingAsAdmin())
        ->test(UserFormModal::class)
        ->call('openCreate')
        ->set('form.identification', '1020304050')
        ->set('form.name', 'Ana')
        ->set('form.lastname', 'Ruiz')
        ->set('form.email', 'ana@example.com')
        ->set('form.role_id', (string) Role::factory()->create()->id)
        ->set('form.can_request_supplies', '1')
        ->call('save')
        ->assertHasErrors(['form.process_id', 'form.service_point_id']);
});

it('guarda el proceso y la habilitación de suministros del usuario', function () {
    $process = Process::factory()->create();
    $servicePoint = ServicePoint::factory()->create();

    Livewire::actingAs(actingAsAdmin())
        ->test(UserFormModal::class)
        ->call('openCreate')
        ->set('form.identification', '1020304050')
        ->set('form.name', 'Ana')
        ->set('form.lastname', 'Ruiz')
        ->set('form.email', 'ana@example.com')
        ->set('form.role_id', (string) Role::factory()->create()->id)
        ->set('form.process_id', (string) $process->id)
        ->set('form.service_point_id', (string) $servicePoint->id)
        ->set('form.can_request_supplies', '1')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'ana@example.com')->first();

    expect($user->process_id)->toBe($process->id)
        ->and($user->can_request_supplies)->toBeTrue();
});

it('habilita en lote la solicitud de suministros solo a usuarios con proceso y sede', function () {
    $ready = User::factory()->create();
    $withoutProcess = User::factory()->create(['process_id' => null]);

    Livewire::actingAs(userWith(['users-read', 'users-update']))
        ->test('pages::user-index')
        ->set('selected', [(string) $ready->id, (string) $withoutProcess->id])
        ->call('setSupplyRequests', true);

    expect($ready->fresh()->can_request_supplies)->toBeTrue()
        ->and($withoutProcess->fresh()->can_request_supplies)->toBeFalse();
});

it('no cambia la solicitud de suministros sin permiso de edición', function () {
    $user = User::factory()->create();

    Livewire::actingAs(userWith('users-read'))
        ->test('pages::user-index')
        ->set('selected', [(string) $user->id])
        ->call('setSupplyRequests', true);

    expect($user->fresh()->can_request_supplies)->toBeFalse();
});

it('filtra usuarios por proceso', function () {
    $process = Process::factory()->create();
    User::factory()->create(['name' => 'Integrante Proceso', 'process_id' => $process->id]);
    User::factory()->create(['name' => 'Otro Usuario']);

    Livewire::actingAs(actingAsAdmin())
        ->test('pages::user-index')
        ->set('filterProcessId', (string) $process->id)
        ->assertSee('Integrante Proceso')
        ->assertDontSee('Otro Usuario');
});

it('restaura usuarios eliminados con permiso de reversar', function () {
    $user = User::factory()->create();
    $user->delete();

    Livewire::actingAs(userWith(['users-read', 'users-reverse']))
        ->test('pages::user-index')
        ->set('selected', [(string) $user->id])
        ->call('restoreSelected');

    expect($user->fresh()->trashed())->toBeFalse();
});
