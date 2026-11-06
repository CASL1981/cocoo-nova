<?php

use App\Livewire\Characteristics\CharacteristicFormModal;
use App\Models\Process;
use Database\Seeders\ProcessSeeder;
use Livewire\Livewire;
use Modules\HumanResources\App\Livewire\Characteristics\HrCharacteristicFormModal;
use Modules\HumanResources\App\Livewire\Characteristics\HrCharacteristicList;
use Modules\HumanResources\App\Livewire\Characteristics\HrCharacteristicOptionFormModal;
use Modules\HumanResources\App\Livewire\Characteristics\HrCharacteristicOptionList;
use Modules\HumanResources\App\Models\HrCharacteristic;
use Modules\HumanResources\App\Models\HrCharacteristicOption;
use Modules\HumanResources\Database\Seeders\PositionSeeder;

it('muestra la pantalla solo con el permiso de lectura', function (): void {
    HrCharacteristic::factory()->positions()->create();

    $this->actingAs(userWith('hr-characteristics-read'))->get(route('hr-characteristics.index'))
        ->assertOk()->assertSee('Características de Talento Humano')->assertSee('Cargos');

    $this->actingAs(userWith())->get(route('hr-characteristics.index'))->assertForbidden();
});

it('crea una característica y bloquea su código cuando tiene opciones', function (): void {
    Livewire::actingAs(actingAsAdmin())
        ->test(HrCharacteristicFormModal::class)
        ->call('openCreate')
        ->set('form.name', 'Tipos de contrato')
        ->set('form.code', 'contract_type')
        ->call('save')
        ->assertHasNoErrors();

    $characteristic = HrCharacteristic::where('code', 'contract_type')->firstOrFail();
    HrCharacteristicOption::factory()->create(['hr_characteristic_id' => $characteristic->id]);

    Livewire::actingAs(actingAsAdmin())
        ->test(HrCharacteristicFormModal::class)
        ->call('openEdit', $characteristic->id)
        ->assertSet('form.codeLocked', true)
        ->set('form.code', 'otro_codigo')
        ->call('save')
        ->assertHasErrors(['form.code']);
});

it('separa sus características de las características generales', function (): void {
    HrCharacteristic::factory()->create(['code' => 'eps']);

    // El mismo código puede existir en las características generales sin conflicto.
    Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicFormModal::class)
        ->call('openCreate')
        ->set('form.name', 'EPS')
        ->set('form.code', 'eps')
        ->call('save')
        ->assertHasNoErrors();
});

it('crea un cargo con proceso y jefe inmediato en la característica Cargos', function (): void {
    $this->seed(ProcessSeeder::class);
    $positions = HrCharacteristic::factory()->positions()->create();
    $manager = HrCharacteristicOption::factory()->create(['hr_characteristic_id' => $positions->id, 'name' => 'Director Comercial y de Mercadeo']);
    $commercial = Process::named('Comercial');

    Livewire::actingAs(actingAsAdmin())
        ->test(HrCharacteristicOptionFormModal::class)
        ->dispatch('hr-characteristic-selected', characteristicId: $positions->id)
        ->call('openCreate')
        ->assertSet('form.isPositions', true)
        ->set('form.name', 'Coordinador de Compras')
        ->call('save')
        ->assertHasErrors(['form.process_id'])
        ->set('form.process_id', (string) $commercial->id)
        ->set('form.parent_id', (string) $manager->id)
        ->call('save')
        ->assertHasNoErrors();

    $option = HrCharacteristicOption::where('name', 'Coordinador de Compras')->firstOrFail();

    expect($option->process_id)->toBe($commercial->id)->and($option->parent_id)->toBe($manager->id);
});

it('no permite que un cargo reporte a sí mismo ni a un subordinado', function (): void {
    $this->seed(ProcessSeeder::class);
    $positions = HrCharacteristic::factory()->positions()->create();
    $boss = HrCharacteristicOption::factory()->create(['hr_characteristic_id' => $positions->id]);
    $employee = HrCharacteristicOption::factory()->create(['hr_characteristic_id' => $positions->id, 'parent_id' => $boss->id]);

    Livewire::actingAs(actingAsAdmin())
        ->test(HrCharacteristicOptionFormModal::class)
        ->call('openEdit', $boss->id)
        ->set('form.process_id', (string) Process::named('Gerencia')->id)
        ->set('form.parent_id', (string) $employee->id)
        ->call('save')
        ->assertHasErrors(['form.parent_id']);

    // Un cargo con subordinados no se elimina.
    Livewire::actingAs(actingAsAdmin())
        ->test(HrCharacteristicOptionList::class)
        ->dispatch('hr-characteristic-selected', characteristicId: $positions->id)
        ->set('selected', [(string) $boss->id])
        ->call('deleteSelected');

    expect($boss->refresh()->trashed())->toBeFalse();
});

it('en otras características no pide ni guarda proceso ni jefe inmediato', function (): void {
    $this->seed(ProcessSeeder::class);
    $contracts = HrCharacteristic::factory()->create(['code' => 'contract_type']);

    Livewire::actingAs(actingAsAdmin())
        ->test(HrCharacteristicOptionFormModal::class)
        ->dispatch('hr-characteristic-selected', characteristicId: $contracts->id)
        ->call('openCreate')
        ->assertSet('form.isPositions', false)
        ->set('form.name', 'Término indefinido')
        ->call('save')
        ->assertHasNoErrors();

    expect(HrCharacteristicOption::where('name', 'Término indefinido')->value('process_id'))->toBeNull();
});

it('no elimina una característica con opciones y la selección muestra sus opciones', function (): void {
    $characteristic = HrCharacteristic::factory()->create();
    HrCharacteristicOption::factory()->create(['hr_characteristic_id' => $characteristic->id, 'name' => 'Opción visible']);

    Livewire::actingAs(actingAsAdmin())
        ->test(HrCharacteristicList::class)
        ->call('selectCharacteristic', $characteristic->id)
        ->assertDispatched('hr-characteristic-selected', characteristicId: $characteristic->id)
        ->set('selected', [(string) $characteristic->id])
        ->call('deleteSelected');

    expect($characteristic->refresh()->trashed())->toBeFalse();

    Livewire::actingAs(actingAsAdmin())
        ->test(HrCharacteristicOptionList::class)
        ->dispatch('hr-characteristic-selected', characteristicId: $characteristic->id)
        ->assertSee('Opción visible');
});

it('carga los 37 cargos del organigrama con su proceso y jefe inmediato', function (): void {
    $this->seed(PositionSeeder::class);
    $this->seed(PositionSeeder::class);

    $positions = HrCharacteristic::where('code', HrCharacteristic::POSITIONS_CODE)->firstOrFail();
    $buyer = HrCharacteristicOption::with(['process', 'parent'])->where('name', 'Coordinador de Compras')->firstOrFail();

    expect($positions->options()->count())->toBe(37)
        ->and($positions->options()->whereNull('process_id')->exists())->toBeFalse()
        ->and($positions->options()->whereNull('parent_id')->pluck('name')->all())->toBe(['Gerente'])
        ->and($buyer->process->name)->toBe('Comercial')
        ->and($buyer->parent->name)->toBe('Director Comercial y de Mercadeo');
});
