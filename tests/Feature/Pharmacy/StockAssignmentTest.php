<?php

use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Modules\Pharmacy\App\Actions\AssignSentQuantity;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Models\InventoryBalance;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;

/** Ítem de un pendiente Terminado con el genérico indicado. */
function finishedItem(array $attributes = []): PendingItem
{
    return PendingItem::factory()
        ->for(Pending::factory()->finished())
        ->create(['generic_code' => 'G1', 'quantity' => 10, 'created_at' => now()->subDays(3), ...$attributes]);
}

/** Saldo cargado para el genérico, homologado con un producto. */
function stockFor(string $genericCode, int $available, int $assigned = 0): InventoryBalance
{
    $item = finishedItem(['generic_code' => 'TMP-'.uniqid()]);

    return InventoryBalance::factory()->create([
        'product_id' => $item->product_id, 'generic_code' => $genericCode,
        'available_quantity' => $available, 'assigned_quantity' => $assigned,
    ]);
}

function assignmentUser()
{
    return userWith(['inventory-balances-read', 'inventory-balances-process']);
}

it('lista solo ítems Sin revisión, Pendiente o Parcial de pendientes terminados', function (): void {
    $visible = finishedItem(['generic_name' => 'VISIBLE SIN REVISION']);
    finishedItem(['generic_name' => 'OCULTO CUMPLIDO', 'status' => PendingItemStatus::Fulfilled]);
    finishedItem(['generic_name' => 'OCULTO AGOTADO', 'status' => PendingItemStatus::OutOfStock]);
    PendingItem::factory()->create(['generic_name' => 'OCULTO PENDIENTE ACTIVO']);

    Livewire::actingAs(assignmentUser())
        ->test('pharmacy::pages.stock-assignment-index')
        ->assertSee($visible->generic_name)
        ->assertDontSee('OCULTO CUMPLIDO')
        ->assertDontSee('OCULTO AGOTADO')
        ->assertDontSee('OCULTO PENDIENTE ACTIVO');
});

it('asigna una parte y deja el ítem Parcial descontando del saldo del genérico', function (): void {
    $balance = stockFor('G1', 6);
    $item = finishedItem();

    Livewire::actingAs(assignmentUser())
        ->test('pharmacy::pages.stock-assignment-index')
        ->call('editRow', $item->id)
        ->set('sendQuantity', '4')
        ->call('saveRow')
        ->assertHasNoErrors()
        ->assertSet('editingId', null);

    $item->refresh();
    expect($item->status)->toBe(PendingItemStatus::Partial)
        ->and($item->send_quantity)->toBe(4)
        ->and($item->date_send->isToday())->toBeTrue()
        ->and($item->number_day)->toBeNull()
        ->and($balance->refresh()->assigned_quantity)->toBe(4);
});

it('suma a lo ya enviado y queda Cumplido con los días calculados', function (): void {
    stockFor('G1', 20);
    $item = finishedItem(['status' => PendingItemStatus::Partial, 'send_quantity' => 4]);

    app(AssignSentQuantity::class)->assign($item, 6);

    $item->refresh();
    expect($item->status)->toBe(PendingItemStatus::Fulfilled)
        ->and($item->send_quantity)->toBe(10)
        ->and($item->number_day)->toBe(3);
});

it('no permite asignar más que el saldo, sumando todos los ítems del ERP del genérico', function (): void {
    stockFor('G1', 3);
    stockFor('G1', 4, assigned: 2);
    $item = finishedItem();

    Livewire::actingAs(assignmentUser())
        ->test('pharmacy::pages.stock-assignment-index')
        ->call('editRow', $item->id)
        ->set('sendQuantity', '6')
        ->call('saveRow')
        ->assertHasErrors(['quantity']);

    expect($item->refresh()->send_quantity)->toBeNull();
});

it('marca Pendiente en bloque sin tocar lo enviado', function (): void {
    $partial = finishedItem(['status' => PendingItemStatus::Partial, 'send_quantity' => 2]);
    $new = finishedItem();

    Livewire::actingAs(assignmentUser())
        ->test('pharmacy::pages.stock-assignment-index')
        ->set('selected', [(string) $partial->id, (string) $new->id])
        ->call('markPendingSelected');

    expect($partial->refresh()->status)->toBe(PendingItemStatus::Pending)
        ->and($partial->send_quantity)->toBe(2)
        ->and($new->refresh()->status)->toBe(PendingItemStatus::Pending);
});

it('filtra solo los ítems con stock', function (): void {
    stockFor('G1', 5);
    finishedItem(['generic_name' => 'CON STOCK']);
    finishedItem(['generic_name' => 'SIN STOCK', 'generic_code' => 'G2']);

    Livewire::actingAs(assignmentUser())
        ->test('pharmacy::pages.stock-assignment-index')
        ->set('onlyWithStock', true)
        ->assertSee('CON STOCK')
        ->assertDontSee('SIN STOCK');
});

it('exige inventory-balances-read para ver y process para asignar', function (): void {
    $this->actingAs(userWith('pendings-read'))
        ->get(route('inventory-balances.assignment'))
        ->assertForbidden();

    $item = finishedItem();
    Livewire::actingAs(userWith('inventory-balances-read'))
        ->test('pharmacy::pages.stock-assignment-index')
        ->call('editRow', $item->id)
        ->assertForbidden();
});

it('exporta a Excel y CSV la tabla de asignación con los filtros aplicados', function (): void {
    stockFor('G1', 5);
    finishedItem();

    Livewire::actingAs(assignmentUser())
        ->test('pharmacy::pages.stock-assignment-index')
        ->call('export', 'xlsx')
        ->assertFileDownloaded()
        ->call('export', 'csv')
        ->assertFileDownloaded();
});

it('selecciona todos los ítems que coinciden con los filtros, no solo la página, y marca Pendiente en todos', function (): void {
    $items = collect(range(1, 12))->map(fn () => finishedItem());

    $page = Livewire::actingAs(assignmentUser())
        ->test('pharmacy::pages.stock-assignment-index')
        ->set('perPage', 10);

    $pageIds = $page->instance()->items->pluck('id')->all();

    $page->call('toggleSelectAll', $pageIds)
        ->assertSeeText('Se seleccionaron los 10 registros de esta página')
        ->assertSee('Seleccionar los 12 registros que coinciden con los filtros')
        ->call('selectAllMatchingRows')
        ->assertSet('selectAllMatching', true)
        ->assertSeeText('Están seleccionados los 12 registros')
        ->call('markPendingSelected')
        ->assertSet('selectAllMatching', false);

    expect(PendingItem::whereKey($items->pluck('id'))->where('status', PendingItemStatus::Pending)->count())->toBe(12);
});

it('cambiar un filtro o desmarcar una fila cancela la selección ampliada', function (): void {
    collect(range(1, 12))->each(fn () => finishedItem());

    $page = Livewire::actingAs(assignmentUser())
        ->test('pharmacy::pages.stock-assignment-index')
        ->set('perPage', 10);
    $pageIds = $page->instance()->items->pluck('id')->map(fn ($id) => (string) $id)->all();

    $page->call('toggleSelectAll', $pageIds)->call('selectAllMatchingRows')
        ->set('selected', array_slice($pageIds, 1))
        ->assertSet('selectAllMatching', false);

    $page->call('toggleSelectAll', $pageIds)->call('selectAllMatchingRows')
        ->set('search', 'otro')
        ->assertSet('selectAllMatching', false)
        ->assertSet('selected', []);
});

it('el cliente no puede activar la selección ampliada sin pasar por el servidor', function (): void {
    Livewire::actingAs(assignmentUser())
        ->test('pharmacy::pages.stock-assignment-index')
        ->set('selectAllMatching', true);
})->throws(CannotUpdateLockedPropertyException::class);

it('asigna automáticamente lo que falta cuando el stock lo cubre, sin pedir la cantidad', function (): void {
    $balance = stockFor('G1', 1650);
    $item = finishedItem(['quantity' => 390]);

    Livewire::actingAs(assignmentUser())
        ->test('pharmacy::pages.stock-assignment-index')
        ->assertSeeHtml("autoAssign({$item->id})")
        ->call('autoAssign', $item->id)
        ->assertDispatched('toast');

    $item->refresh();
    expect($item->status)->toBe(PendingItemStatus::Fulfilled)
        ->and($item->send_quantity)->toBe(390)
        ->and($balance->refresh()->assigned_quantity)->toBe(390);
});

it('en un Parcial asigna automáticamente solo el resto', function (): void {
    stockFor('G1', 10);
    $item = finishedItem(['quantity' => 10, 'send_quantity' => 4, 'status' => PendingItemStatus::Partial]);

    Livewire::actingAs(assignmentUser())
        ->test('pharmacy::pages.stock-assignment-index')
        ->call('autoAssign', $item->id);

    expect($item->refresh()->send_quantity)->toBe(10)
        ->and($item->status)->toBe(PendingItemStatus::Fulfilled);
});

it('no muestra ni ejecuta la asignación automática si el stock no alcanza', function (): void {
    stockFor('G1', 5);
    $item = finishedItem(['quantity' => 10]);

    Livewire::actingAs(assignmentUser())
        ->test('pharmacy::pages.stock-assignment-index')
        ->assertDontSeeHtml("autoAssign({$item->id})")
        ->call('autoAssign', $item->id);

    expect($item->refresh()->send_quantity)->toBeNull();
});

it('la asignación automática exige inventory-balances-process', function (): void {
    stockFor('G1', 50);
    $item = finishedItem();

    Livewire::actingAs(userWith('inventory-balances-read'))
        ->test('pharmacy::pages.stock-assignment-index')
        ->assertDontSeeHtml("autoAssign({$item->id})")
        ->call('autoAssign', $item->id)
        ->assertForbidden();
});

it('no ofrece la asignación automática si el stock es igual a lo que falta (debe ser mayor)', function (): void {
    stockFor('G1', 10);
    $item = finishedItem(['quantity' => 10]);

    Livewire::actingAs(assignmentUser())
        ->test('pharmacy::pages.stock-assignment-index')
        ->assertDontSeeHtml("autoAssign({$item->id})")
        ->call('autoAssign', $item->id);

    expect($item->refresh()->send_quantity)->toBeNull();
});
