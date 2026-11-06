<?php

use Livewire\Livewire;
use Modules\Pharmacy\App\Actions\AssignSentQuantity;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Models\InventoryBalance;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;

/** Ítem Cumplido de un pendiente terminado. */
function fulfilledItem(array $attributes = []): PendingItem
{
    return PendingItem::factory()->for(Pending::factory()->finished())->create([
        'generic_code' => 'G1', 'quantity' => 10, 'send_quantity' => 10, 'date_send' => now()->toDateString(),
        'number_day' => 2, 'status' => PendingItemStatus::Fulfilled, ...$attributes,
    ]);
}

it('muestra ítems de todas las sedes y estados con permiso de lectura de pendientes', function (): void {
    PendingItem::factory()->create(['generic_name' => 'ITEM ACTIVO']);
    fulfilledItem(['generic_name' => 'ITEM CUMPLIDO']);
    PendingItem::factory()->outOfStock()->create(['generic_name' => 'ITEM AGOTADO']);

    Livewire::actingAs(userWith('pendings-read'))
        ->test('pharmacy::pages.pending-item-query')
        ->assertSee('ITEM ACTIVO')
        ->assertSee('ITEM CUMPLIDO')
        ->assertSee('ITEM AGOTADO');
});

it('filtra por varios estados del ítem y por rango de fecha de la solicitud', function (): void {
    $old = Pending::factory()->finished()->create(['created_at' => now()->subMonth()]);
    PendingItem::factory()->for($old)->create(['generic_name' => 'SOLICITUD ANTIGUA', 'status' => PendingItemStatus::Pending]);
    fulfilledItem(['generic_name' => 'RECIENTE CUMPLIDO']);
    PendingItem::factory()->create(['generic_name' => 'RECIENTE SIN REVISION']);

    Livewire::actingAs(userWith('pendings-read'))
        ->test('pharmacy::pages.pending-item-query')
        ->set('filterStatuses', ['fulfilled', 'pending'])
        ->set('filterRequestFrom', now()->subWeek()->toDateString())
        ->assertSee('RECIENTE CUMPLIDO')
        ->assertDontSee('SOLICITUD ANTIGUA')
        ->assertDontSee('RECIENTE SIN REVISION');
});

it('corrige el enviado de un Cumplido a Parcial y devuelve la diferencia al saldo vigente', function (): void {
    $item = fulfilledItem();
    $balance = InventoryBalance::factory()->create([
        'product_id' => $item->product_id, 'generic_code' => 'G1', 'available_quantity' => 50, 'assigned_quantity' => 10,
        'created_at' => now()->subHour(),
    ]);

    Livewire::actingAs(userWith(['pendings-read', 'inventory-balances-reverse']))
        ->test('pharmacy::pages.pending-item-query')
        ->set('selected', [(string) $item->id])
        ->call('openCorrect')
        ->assertSet('correctTotal', '10')
        ->set('correctTotal', '6')
        ->call('saveCorrection')
        ->assertHasNoErrors()
        ->assertSet('showCorrect', false);

    $item->refresh();
    expect($item->status)->toBe(PendingItemStatus::Partial)
        ->and($item->send_quantity)->toBe(6)
        ->and($item->number_day)->toBeNull()
        ->and($balance->refresh()->assigned_quantity)->toBe(6);
});

it('al corregir a cero deja el ítem Pendiente sin fecha de envío', function (): void {
    $item = fulfilledItem();

    app(AssignSentQuantity::class)->correct($item, 0);

    $item->refresh();
    expect($item->status)->toBe(PendingItemStatus::Pending)
        ->and($item->send_quantity)->toBeNull()
        ->and($item->date_send)->toBeNull();
});

it('no devuelve saldo si la asignación es anterior a la carga vigente', function (): void {
    $item = fulfilledItem(['date_send' => now()->subDays(5)->toDateString()]);
    $balance = InventoryBalance::factory()->create([
        'product_id' => $item->product_id, 'generic_code' => 'G1', 'available_quantity' => 50, 'assigned_quantity' => 3,
    ]);

    app(AssignSentQuantity::class)->correct($item, 4);

    expect($balance->refresh()->assigned_quantity)->toBe(3);
});

it('exige inventory-balances-reverse para corregir', function (): void {
    $item = fulfilledItem();

    Livewire::actingAs(userWith('pendings-read'))
        ->test('pharmacy::pages.pending-item-query')
        ->set('selected', [(string) $item->id])
        ->call('openCorrect')
        ->assertForbidden();
});

it('niega la consulta sin permiso de lectura de pendientes', function (): void {
    $this->actingAs(userWith('inventory-balances-read'))
        ->get(route('pendings.items'))
        ->assertForbidden();
});
