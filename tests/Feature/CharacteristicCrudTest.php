<?php

use App\Livewire\Characteristics\CharacteristicFormModal;
use App\Livewire\Characteristics\CharacteristicList;
use App\Livewire\Characteristics\CharacteristicOptionFormModal;
use App\Livewire\Characteristics\CharacteristicOptionList;
use App\Models\Characteristic;
use App\Models\CharacteristicOption;
use App\Models\User;
use Livewire\Livewire;

it('crea una característica', function () {
    Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicFormModal::class)
        ->call('openCreate')
        ->set('form.name', 'MODALIDAD DE CONTRATACION')
        ->set('form.code', 'contracting_modality')
        ->call('save')
        ->assertHasNoErrors();

    expect(Characteristic::where('code', 'contracting_modality')->exists())->toBeTrue();
});

it('exige que el código sea snake_case en minúsculas', function () {
    Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicFormModal::class)
        ->call('openCreate')
        ->set('form.name', 'EPS')
        ->set('form.code', 'EPS Modalidad')
        ->call('save')
        ->assertHasErrors(['form.code']);
});

it('seleccionar una característica filtra sus opciones', function () {
    $characteristic = Characteristic::factory()->create(['code' => 'eps']);

    $options = Livewire::actingAs(actingAsAdmin())->test(CharacteristicOptionList::class);
    $list = Livewire::actingAs(actingAsAdmin())->test(CharacteristicList::class);

    $list->call('selectCharacteristic', $characteristic->id)
        ->assertDispatched('characteristic-selected', characteristicId: $characteristic->id);
});

it('crea una opción asociada a la característica activa', function () {
    $characteristic = Characteristic::factory()->create();

    Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicOptionFormModal::class)
        ->call('openCreate', $characteristic->id)
        ->set('form.name', 'CAPITADO')
        ->call('save')
        ->assertHasNoErrors();

    expect($characteristic->options()->where('name', 'CAPITADO')->exists())->toBeTrue();
});

it('impide eliminar una característica con opciones', function () {
    $characteristic = Characteristic::factory()->create();
    $characteristic->options()->create(['name' => 'X', 'status' => 'active']);

    Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicList::class)
        ->set('selected', [(string) $characteristic->id])
        ->call('deleteSelected');

    expect(Characteristic::find($characteristic->id))->not->toBeNull();
});

it('carga las opciones al seleccionar una característica', function (): void {
    // Usuario con permiso de lectura y una característica con una opción.
    $user = User::factory()->create()->givePermissionTo('characteristics-read');
    $characteristic = Characteristic::factory()->create();
    CharacteristicOption::factory()->for($characteristic)->create();

    Livewire::actingAs($user)
        ->test(CharacteristicOptionList::class)
        ->dispatch('characteristic-selected', characteristicId: $characteristic->id)
        ->assertSet('characteristicId', $characteristic->id)
        ->assertOk();
});

it('restaura una característica eliminada', function (): void {
    $user = User::factory()->create()->givePermissionTo(['characteristics-read', 'characteristics-reverse']);
    $characteristic = Characteristic::factory()->create();
    $characteristic->delete();

    Livewire::actingAs($user)
        ->test(CharacteristicList::class)
        ->set('selected', [(string) $characteristic->id])
        ->call('restoreSelected');

    expect($characteristic->fresh()->trashed())->toBeFalse();
});

it('exporta las características a excel', function (): void {
    $user = User::factory()->create()->givePermissionTo('characteristics-read');
    Characteristic::factory()->create();

    Livewire::actingAs($user)
        ->test(CharacteristicList::class)
        ->call('export', 'xlsx')
        ->assertFileDownloaded();
});

it('impide modificar opciones de una característica eliminada', function (): void {
    $user = User::factory()->create()->givePermissionTo(['characteristics-update', 'characteristics-delete']);
    $option = CharacteristicOption::factory()->create();
    $option->characteristic->delete();

    // La Policy niega la edición y eliminación aunque el usuario tenga los permisos.
    expect($user->can('update', $option->fresh()))->toBeFalse()
        ->and($user->can('delete', $option->fresh()))->toBeFalse();
});

it('busca opciones dentro de la característica activa y limpia la búsqueda al cambiarla', function () {
    $characteristic = Characteristic::factory()->create();
    CharacteristicOption::factory()->create(['characteristic_id' => $characteristic->id, 'name' => 'CAPITADO']);
    CharacteristicOption::factory()->create(['characteristic_id' => $characteristic->id, 'name' => 'EVENTO']);
    $other = Characteristic::factory()->create();

    Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicOptionList::class)
        ->dispatch('characteristic-selected', characteristicId: $characteristic->id)
        ->set('filterName', 'capi')
        ->assertSee('CAPITADO')
        ->assertDontSee('EVENTO')
        ->dispatch('characteristic-selected', characteristicId: $other->id)
        ->assertSet('filterName', '');
});

it('al editar una opción limpia la selección para poder editar la siguiente sin desmarcar', function () {
    $characteristic = Characteristic::factory()->create();
    $first = CharacteristicOption::factory()->create(['characteristic_id' => $characteristic->id]);
    $second = CharacteristicOption::factory()->create(['characteristic_id' => $characteristic->id]);

    Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicOptionList::class)
        ->dispatch('characteristic-selected', characteristicId: $characteristic->id)
        ->set('selected', [(string) $first->id])
        ->call('editSelected', 'open-characteristic-option-edit', 'optionId')
        ->assertDispatched('open-characteristic-option-edit', optionId: $first->id)
        ->assertSet('selected', [])
        ->set('selected', [(string) $second->id])
        ->call('editSelected', 'open-characteristic-option-edit', 'optionId')
        ->assertDispatched('open-characteristic-option-edit', optionId: $second->id);
});

it('editSelected no abre nada con varias filas marcadas ni acepta eventos ajenos a la edición', function () {
    $characteristic = Characteristic::factory()->create();
    $options = CharacteristicOption::factory()->count(2)->create(['characteristic_id' => $characteristic->id]);

    $component = Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicOptionList::class)
        ->dispatch('characteristic-selected', characteristicId: $characteristic->id);

    $component->set('selected', $options->pluck('id')->map(fn ($id) => (string) $id)->all())
        ->call('editSelected', 'open-characteristic-option-edit', 'optionId')
        ->assertNotDispatched('open-characteristic-option-edit');

    $component->set('selected', [(string) $options[0]->id])
        ->call('editSelected', 'toast', 'message')
        ->assertNotDispatched('toast')
        ->assertCount('selected', 1);
});

it('bloquea el cambio de código de una característica con opciones', function () {
    $characteristic = Characteristic::factory()->create(['code' => 'payment_condition']);
    CharacteristicOption::factory()->create(['characteristic_id' => $characteristic->id]);

    Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicFormModal::class)
        ->call('openEdit', $characteristic->id)
        ->assertSet('form.codeLocked', true)
        ->set('form.code', 'payment_condition_new')
        ->call('save')
        ->assertHasErrors(['form.code']);

    // Aunque el cliente desbloquee el campo, la acción lo impide.
    Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicFormModal::class)
        ->call('openEdit', $characteristic->id)
        ->set('form.codeLocked', false)
        ->set('form.code', 'payment_condition_new')
        ->call('save')
        ->assertHasErrors(['form.code']);

    expect($characteristic->fresh()->code)->toBe('payment_condition');
});

it('permite cambiar el código de una característica sin opciones', function () {
    $characteristic = Characteristic::factory()->create(['code' => 'temporary_code']);

    Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicFormModal::class)
        ->call('openEdit', $characteristic->id)
        ->assertSet('form.codeLocked', false)
        ->set('form.code', 'final_code')
        ->call('save')
        ->assertHasNoErrors();

    expect($characteristic->fresh()->code)->toBe('final_code');
});
