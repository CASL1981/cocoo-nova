<?php

use App\Enums\Status;
use App\Models\Characteristic;
use App\Models\CharacteristicOption;
use App\Models\User;
use Livewire\Livewire;
use Modules\Pharmacy\App\Livewire\StockOuts\StockOutFormModal;
use Modules\Pharmacy\App\Models\StockOut;

it('niega el acceso al listado sin permiso', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('stock-outs.index'))
        ->assertForbidden();
});

it('muestra el listado con permiso de lectura', function (): void {
    $user = User::factory()->create()->givePermissionTo('stock-outs-read');
    $stockOut = StockOut::factory()->create();

    $this->actingAs($user)
        ->get(route('stock-outs.index'))
        ->assertOk()
        ->assertSee($stockOut->generic_code);
});

it('crea un agotado desde el modal', function (): void {
    $user = User::factory()->create()->givePermissionTo(['stock-outs-read', 'stock-outs-create']);

    Livewire::actingAs($user)
        ->test(StockOutFormModal::class)
        ->dispatch('open-stock-out-create')
        ->set('form.generic_code', '12345')
        ->set('form.generic_name', 'Acetaminofén')
        ->set('form.product_name', 'Dolex 500 mg')
        ->set('form.circular_number', 'C-001')
        ->set('form.circular_date', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('stock-out-saved');

    expect(StockOut::where('generic_code', '12345')->first()?->status)->toBe(Status::Active);
});

it('acepta una clasificación que es opción activa de la característica', function (): void {
    $user = User::factory()->create()->givePermissionTo(['stock-outs-read', 'stock-outs-create']);

    $characteristic = Characteristic::factory()->create(['code' => StockOut::CLASSIFICATION_CHARACTERISTIC_CODE]);
    CharacteristicOption::factory()->create(['characteristic_id' => $characteristic->id, 'name' => 'MEDICAMENTOS']);

    Livewire::actingAs($user)
        ->test(StockOutFormModal::class)
        ->dispatch('open-stock-out-create')
        ->set('form.generic_code', '12345')
        ->set('form.generic_name', 'Acetaminofén')
        ->set('form.product_name', 'Dolex 500 mg')
        ->set('form.classification', 'MEDICAMENTOS')
        ->set('form.circular_number', 'C-001')
        ->set('form.circular_date', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasNoErrors();

    expect(StockOut::where('generic_code', '12345')->first()?->classification)->toBe('MEDICAMENTOS');
});

it('rechaza una clasificación que no es opción activa de la característica', function (): void {
    $user = User::factory()->create()->givePermissionTo(['stock-outs-read', 'stock-outs-create']);

    $characteristic = Characteristic::factory()->create(['code' => StockOut::CLASSIFICATION_CHARACTERISTIC_CODE]);
    CharacteristicOption::factory()->create(['characteristic_id' => $characteristic->id, 'name' => 'MEDICAMENTOS']);

    Livewire::actingAs($user)
        ->test(StockOutFormModal::class)
        ->dispatch('open-stock-out-create')
        ->set('form.generic_code', '12345')
        ->set('form.generic_name', 'Acetaminofén')
        ->set('form.product_name', 'Dolex 500 mg')
        ->set('form.classification', 'NO EXISTE')
        ->set('form.circular_number', 'C-001')
        ->set('form.circular_date', now()->format('Y-m-d'))
        ->call('save')
        ->assertHasErrors(['form.classification']);
});

it('alterna el estado de los agotados seleccionados', function (): void {
    $user = User::factory()->create()->givePermissionTo(['stock-outs-read', 'stock-outs-toggle']);
    $stockOut = StockOut::factory()->create();

    Livewire::actingAs($user)
        ->test('pharmacy::pages.stock-out-index')
        ->set('selected', [(string) $stockOut->id])
        ->call('toggleSelected');

    expect($stockOut->fresh()->status)->toBe(Status::Inactive);
});

it('el botón Editar de la barra estándar abre la edición y limpia la selección', function (): void {
    $stockOut = StockOut::factory()->create();

    Livewire::actingAs(actingAsAdmin())
        ->test('pharmacy::pages.stock-out-index')
        ->set('selected', [(string) $stockOut->id])
        ->assertSeeHtml("editSelected('open-stock-out-edit', 'stockOutId')")
        ->call('editSelected', 'open-stock-out-edit', 'stockOutId')
        ->assertDispatched('open-stock-out-edit', stockOutId: $stockOut->id)
        ->assertSet('selected', []);
});
