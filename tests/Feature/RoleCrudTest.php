<?php

use App\Livewire\Roles\RoleFormModal;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

it('filtra roles por nombre', function () {
    Role::factory()->create(['name' => 'Administrador']);
    Role::factory()->create(['name' => 'Contabilidad']);

    $component = Livewire::actingAs(actingAsAdmin())
        ->test('pages::role-index')
        ->set('filterName', 'Admin');

    expect($component->instance()->roles->pluck('name')->all())
        ->toBe(['Administrador']);
});

it('valida y crea un rol', function () {
    $component = Livewire::actingAs(actingAsAdmin())
        ->test(RoleFormModal::class)
        ->call('openCreate');

    $component->call('save')->assertHasErrors(['form.name']);

    $component->set('form.name', 'Supervisor')
        ->set('form.description', 'Supervisa operaciones diarias')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast')
        ->assertSet('show', false);

    expect(Role::where('name', 'Supervisor')->exists())->toBeTrue();
});

it('impide eliminar un rol con usuarios asignados', function () {
    $role = Role::factory()->create();
    User::factory()->create(['role_id' => $role->id]);

    Livewire::actingAs(actingAsAdmin())
        ->test('pages::role-index')
        ->call('delete', $role->id);

    expect(Role::find($role->id))->not->toBeNull();
});
