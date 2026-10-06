<?php

use App\Livewire\ServicePoints\ServicePointFormModal;
use App\Models\Role;
use App\Models\ServicePoint;
use App\Models\User;
use Livewire\Livewire;

it('filtra centros de costos por nombre', function () {
    ServicePoint::factory()->create(['name' => 'FARMACIA VALENCIA']);
    ServicePoint::factory()->create(['name' => 'FARMACIA MONTELIBANO']);

    $component = Livewire::actingAs(actingAsAdmin())
        ->test('pages::service-point-index')
        ->set('filterName', 'VALENCIA');

    expect($component->instance()->servicePoints->pluck('name')->all())
        ->toBe(['FARMACIA VALENCIA']);
});

it('valida y crea una centro de costo', function () {
    $component = Livewire::actingAs(actingAsAdmin())
        ->test(ServicePointFormModal::class)
        ->call('openCreate');

    $component->call('save')->assertHasErrors(['form.code', 'form.name']);

    $component->set('form.code', '9999')
        ->set('form.name', 'FARMACIA DE PRUEBA')
        ->set('form.type', 'pharmacy')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('show', false);

    expect(ServicePoint::where('code', '9999')->exists())->toBeTrue();
});

it('impide crear dos centros de costos con el mismo código', function () {
    ServicePoint::factory()->create(['code' => '9999']);

    Livewire::actingAs(actingAsAdmin())
        ->test(ServicePointFormModal::class)
        ->call('openCreate')
        ->set('form.code', '9999')
        ->set('form.name', 'OTRA SEDE')
        ->call('save')
        ->assertHasErrors(['form.code']);
});

it('impide eliminar una centro de costo con usuarios asignados', function () {
    $servicePoint = ServicePoint::factory()->create();
    User::factory()->create(['service_point_id' => $servicePoint->id]);

    Livewire::actingAs(actingAsAdmin())
        ->test('pages::service-point-index')
        ->call('delete', $servicePoint->id);

    expect(ServicePoint::find($servicePoint->id))->not->toBeNull();
});

it('bloquea el acceso directo a /service-points sin el permiso service-points-read', function () {
    $role = Role::factory()->create();
    $role->syncPermissions([]);

    $user = User::factory()->create(['role_id' => $role->id]);
    $user->assignRole($role);

    $this->actingAs($user)->get(route('service-points.index'))->assertForbidden();
});

it('exporta el listado a excel', function () {
    ServicePoint::factory()->count(3)->create();

    Livewire::actingAs(actingAsAdmin())
        ->test('pages::service-point-index')
        ->call('export', 'xlsx')
        ->assertFileDownloaded();
});
