<?php

use App\Models\Characteristic;
use App\Models\CharacteristicOption;
use App\Models\ServicePoint;
use App\Models\User;
use Livewire\Livewire;
use Modules\Pharmacy\App\Enums\PendingStatus;
use Modules\Pharmacy\App\Livewire\Pendings\PendingFormModal;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;

/** Crea la característica de categorías con una opción activa. */
function seedPendingCategory(string $option = 'MEDICAMENTOS'): void
{
    $characteristic = Characteristic::factory()->create(['code' => Pending::CATEGORY_CHARACTERISTIC_CODE]);
    CharacteristicOption::factory()->create(['characteristic_id' => $characteristic->id, 'name' => $option]);
}

it('niega el acceso al listado sin permiso', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('pendings.index'))
        ->assertForbidden();
});

it('muestra el listado con permiso de lectura', function (): void {
    $pending = Pending::factory()->create(['reason' => 'Reposición mensual']);

    $this->actingAs(userWith('pendings-read'))
        ->get(route('pendings.index'))
        ->assertOk()
        ->assertSee('Reposición mensual');
});

it('crea un pendiente con el solicitante y la sede del usuario por defecto', function (): void {
    seedPendingCategory();
    $user = userWith(['pendings-read', 'pendings-create']);

    Livewire::actingAs($user)
        ->test(PendingFormModal::class)
        ->dispatch('open-pending-create')
        ->assertSet('form.destination_id', (string) $user->service_point_id)
        ->set('form.type', 'PD')
        ->set('form.category', 'MEDICAMENTOS')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('pending-saved');

    $pending = Pending::sole();
    expect($pending->user_id)->toBe($user->id)
        ->and($pending->destination_id)->toBe($user->service_point_id)
        ->and($pending->status)->toBe(PendingStatus::Active);
});

it('exige tipo, categoría y destino', function (): void {
    $user = userWith(['pendings-read', 'pendings-create']);

    Livewire::actingAs($user)
        ->test(PendingFormModal::class)
        ->dispatch('open-pending-create')
        ->set('form.destination_id', '')
        ->call('save')
        ->assertHasErrors(['form.type', 'form.category', 'form.destination_id']);
});

it('rechaza una categoría que no es opción activa de la característica', function (): void {
    seedPendingCategory();

    Livewire::actingAs(userWith(['pendings-read', 'pendings-create']))
        ->test(PendingFormModal::class)
        ->dispatch('open-pending-create')
        ->set('form.type', 'RQ')
        ->set('form.category', 'NO EXISTE')
        ->call('save')
        ->assertHasErrors(['form.category']);
});

it('no permite editar un pendiente terminado', function (): void {
    $pending = Pending::factory()->finished()->create();

    Livewire::actingAs(userWith(['pendings-read', 'pendings-update']))
        ->test(PendingFormModal::class)
        ->call('openEdit', $pending->id)
        ->assertForbidden();
});

/** Intenta terminar el pendiente (con al menos un ítem) desde el listado con el usuario dado. */
function finishPendingAs(User $user, Pending $pending, bool $withItem = true): void
{
    if ($withItem && ! $pending->items()->exists()) {
        PendingItem::factory()->for($pending)->create();
    }

    Livewire::actingAs($user)
        ->test('pharmacy::pages.pending-index')
        ->set('selected', [(string) $pending->id])
        ->call('finishSelected');
}

it('permite al solicitante terminar su pendiente de su sede sin permiso de procesar', function (): void {
    $user = userWith('pendings-read');
    $pending = Pending::factory()->for($user)->create(['destination_id' => $user->service_point_id]);

    finishPendingAs($user, $pending);

    expect($pending->fresh()->status)->toBe(PendingStatus::Finished);
});

it('no termina un pendiente sin ítems', function (): void {
    $user = userWith(['pendings-read', 'pendings-process']);
    $pending = Pending::factory()->for($user)->create(['destination_id' => $user->service_point_id]);

    finishPendingAs($user, $pending, withItem: false);

    expect($pending->fresh()->status)->toBe(PendingStatus::Active);
});

it('solo termina pendientes ajenos de su sede con permiso de procesar', function (): void {
    $servicePoint = ServicePoint::factory()->create();
    $pending = Pending::factory()->create(['destination_id' => $servicePoint->id]);

    $reader = userWith('pendings-read');
    $reader->update(['service_point_id' => $servicePoint->id]);

    finishPendingAs($reader, $pending);
    expect($pending->fresh()->status)->toBe(PendingStatus::Active);

    $processor = userWith(['pendings-read', 'pendings-process']);
    $processor->update(['service_point_id' => $servicePoint->id]);

    finishPendingAs($processor, $pending);
    expect($pending->fresh()->status)->toBe(PendingStatus::Finished);
});

it('no termina pendientes de otra sede aunque tenga permiso de procesar o sea el solicitante', function (): void {
    $user = userWith(['pendings-read', 'pendings-process']);
    $pending = Pending::factory()->for($user)->create(['destination_id' => ServicePoint::factory()]);

    finishPendingAs($user, $pending);

    expect($pending->fresh()->status)->toBe(PendingStatus::Active);
});

it('permite a Oficina Principal terminar pendientes de cualquier sede con permiso de procesar', function (): void {
    $headquarters = ServicePoint::factory()->create(['code' => ServicePoint::HEADQUARTERS_CODE]);
    $pending = Pending::factory()->create();

    $withoutPermission = userWith('pendings-read');
    $withoutPermission->update(['service_point_id' => $headquarters->id]);
    finishPendingAs($withoutPermission, $pending);
    expect($pending->fresh()->status)->toBe(PendingStatus::Active);

    $processor = userWith(['pendings-read', 'pendings-process']);
    $processor->update(['service_point_id' => $headquarters->id]);
    finishPendingAs($processor, $pending);
    expect($pending->fresh()->status)->toBe(PendingStatus::Finished);
});

it('reabre un pendiente terminado solo con permiso de reversar', function (): void {
    $pending = Pending::factory()->finished()->create();

    Livewire::actingAs(userWith('pendings-read'))
        ->test('pharmacy::pages.pending-index')
        ->set('selected', [(string) $pending->id])
        ->call('reopenSelected');

    expect($pending->fresh()->status)->toBe(PendingStatus::Finished);

    Livewire::actingAs(userWith(['pendings-read', 'pendings-reverse']))
        ->test('pharmacy::pages.pending-index')
        ->set('selected', [(string) $pending->id])
        ->call('reopenSelected');

    expect($pending->fresh()->status)->toBe(PendingStatus::Active);
});

it('no elimina pendientes terminados', function (): void {
    $pending = Pending::factory()->finished()->create();

    Livewire::actingAs(userWith(['pendings-read', 'pendings-delete']))
        ->test('pharmacy::pages.pending-index')
        ->set('selected', [(string) $pending->id])
        ->call('deleteSelected');

    expect($pending->fresh()->trashed())->toBeFalse();
});

it('limita el listado a la sede preferida del usuario', function (): void {
    $preferred = ServicePoint::factory()->create();
    $mine = Pending::factory()->create(['destination_id' => $preferred->id]);
    $other = Pending::factory()->create();

    $user = userWith('pendings-read');
    $user->update(['pending_service_point_id' => $preferred->id]);

    $ids = Livewire::actingAs($user)
        ->test('pharmacy::pages.pending-index')
        ->instance()->pendings->pluck('id')->all();

    expect($ids)->toBe([$mine->id]);

    $user->update(['pending_service_point_id' => null]);

    $ids = Livewire::actingAs($user->fresh())
        ->test('pharmacy::pages.pending-index')
        ->instance()->pendings->pluck('id')->all();

    expect($ids)->toContain($mine->id, $other->id);
});

it('guarda la preferencia de sede de pendientes desde el perfil', function (): void {
    $user = User::factory()->create();
    $servicePoint = ServicePoint::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('pendingServicePointId', (string) $servicePoint->id)
        ->call('updatePendingPreference')
        ->assertHasNoErrors();

    expect($user->fresh()->pending_service_point_id)->toBe($servicePoint->id);
});

it('notifica al usuario cuando no puede terminar un pendiente de otra sede', function (): void {
    $user = userWith(['pendings-read', 'pendings-process']);
    $pending = Pending::factory()->create(['destination_id' => ServicePoint::factory()]);

    Livewire::actingAs($user)
        ->test('pharmacy::pages.pending-index')
        ->set('selected', [(string) $pending->id])
        ->call('finishSelected')
        ->assertDispatched('toast', fn (string $name, array $params) => $params['type'] === 'error'
            && str_contains($params['message'], '1 registro(s) omitido(s)'))
        ->assertNotDispatched('toast', fn (string $name, array $params) => $params['type'] === 'success');

    expect($pending->fresh()->status)->toBe(PendingStatus::Active);
});

it('permite editar un pendiente conservando una categoría que ya no es opción activa', function (): void {
    seedPendingCategory();
    $user = userWith(['pendings-read', 'pendings-update']);
    $pending = Pending::factory()->create(['category' => 'CATEGORIA HISTORICA', 'destination_id' => $user->service_point_id]);

    Livewire::actingAs($user)
        ->test(PendingFormModal::class)
        ->call('openEdit', $pending->id)
        ->set('form.reason', 'Motivo ajustado')
        ->call('save')
        ->assertHasNoErrors();

    expect($pending->fresh()->reason)->toBe('Motivo ajustado');
});

it('no permite editar un pendiente de otra sede', function (): void {
    $pending = Pending::factory()->create(['destination_id' => ServicePoint::factory()]);

    Livewire::actingAs(userWith(['pendings-read', 'pendings-update']))
        ->test(PendingFormModal::class)
        ->call('openEdit', $pending->id)
        ->assertForbidden();
});

it('deshabilita Editar en el listado para un pendiente de otra sede', function (): void {
    $user = userWith(['pendings-read', 'pendings-update']);
    $own = Pending::factory()->create(['destination_id' => $user->service_point_id]);
    $other = Pending::factory()->create(['destination_id' => ServicePoint::factory()]);

    $component = Livewire::actingAs($user)->test('pharmacy::pages.pending-index');

    expect($component->set('selected', [(string) $own->id])->instance()->singleEditableSelected())->toBeTrue()
        ->and($component->set('selected', [(string) $other->id])->instance()->singleEditableSelected())->toBeFalse();
});

it('permite a Oficina Principal editar pendientes de cualquier sede', function (): void {
    $headquarters = ServicePoint::factory()->create(['code' => ServicePoint::HEADQUARTERS_CODE]);
    $user = userWith(['pendings-read', 'pendings-update']);
    $user->update(['service_point_id' => $headquarters->id]);
    $pending = Pending::factory()->create();

    Livewire::actingAs($user)
        ->test(PendingFormModal::class)
        ->call('openEdit', $pending->id)
        ->assertSet('show', true);
});

it('elimina solo pendientes de su sede y notifica los omitidos', function (): void {
    $user = userWith(['pendings-read', 'pendings-delete']);
    $own = Pending::factory()->create(['destination_id' => $user->service_point_id]);
    $other = Pending::factory()->create(['destination_id' => ServicePoint::factory()]);

    $component = Livewire::actingAs($user)
        ->test('pharmacy::pages.pending-index')
        ->set('selected', [(string) $own->id, (string) $other->id])
        ->call('deleteSelected');

    // Se despachan dos toasts (éxito y omitidos); assertDispatched solo evalúa el primero.
    $toasts = collect($component->effects['dispatches'] ?? [])->where('name', 'toast')->pluck('params');

    expect($toasts->pluck('type')->all())->toBe(['success', 'error'])
        ->and($toasts->last()['message'])->toContain('1 registro(s) omitido(s)');

    expect($own->fresh()->trashed())->toBeTrue()
        ->and($other->fresh()->trashed())->toBeFalse();
});

it('solo ofrece y acepta la sede propia como destino fuera de Oficina Principal', function (): void {
    seedPendingCategory();
    $user = userWith(['pendings-read', 'pendings-create']);
    $otherServicePoint = ServicePoint::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(PendingFormModal::class)
        ->dispatch('open-pending-create');

    expect(array_keys($component->instance()->destinationOptions))->toBe([$user->service_point_id]);

    $component->set('form.type', 'PD')
        ->set('form.category', 'MEDICAMENTOS')
        ->set('form.destination_id', (string) $otherServicePoint->id)
        ->call('save')
        ->assertHasErrors(['form.destination_id']);

    expect(Pending::count())->toBe(0);
});

it('permite a Oficina Principal elegir cualquier destino', function (): void {
    seedPendingCategory();
    $headquarters = ServicePoint::factory()->create(['code' => ServicePoint::HEADQUARTERS_CODE]);
    $user = userWith(['pendings-read', 'pendings-create']);
    $user->update(['service_point_id' => $headquarters->id]);
    $otherServicePoint = ServicePoint::factory()->create();

    Livewire::actingAs($user)
        ->test(PendingFormModal::class)
        ->dispatch('open-pending-create')
        ->set('form.type', 'RQ')
        ->set('form.category', 'MEDICAMENTOS')
        ->set('form.destination_id', (string) $otherServicePoint->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(Pending::sole()->destination_id)->toBe($otherServicePoint->id);
});
