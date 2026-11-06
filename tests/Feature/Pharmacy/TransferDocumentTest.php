<?php

use Livewire\Livewire;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;

/** Ítem Cumplido de un pendiente terminado, para el C.C. indicado. */
function deliveredItem(string $destination = '1300', array $attributes = []): PendingItem
{
    return PendingItem::factory()->for(Pending::factory()->finished())->create([
        'destination_code' => $destination, 'quantity' => 5, 'send_quantity' => 5,
        'date_send' => now()->toDateString(), 'number_day' => 0, 'status' => PendingItemStatus::Fulfilled, ...$attributes,
    ]);
}

function tbPage(array $permissions = ['inventory-balances-read', 'inventory-balances-process'])
{
    return Livewire::actingAs(userWith($permissions))->test('pharmacy::pages.transfer-document-index');
}

it('lista solo los ítems Cumplidos sin TB por defecto', function (): void {
    deliveredItem(attributes: ['generic_name' => 'CUMPLIDO SIN TB']);
    deliveredItem(attributes: ['generic_name' => 'CUMPLIDO CON TB', 'doc_number' => '555']);
    deliveredItem(attributes: ['generic_name' => 'PARCIAL', 'status' => PendingItemStatus::Partial, 'number_day' => null]);

    tbPage()
        ->assertSee('CUMPLIDO SIN TB')
        ->assertDontSee('CUMPLIDO CON TB')
        ->assertDontSee('PARCIAL')
        ->set('filterTb', 'with')
        ->assertSee('CUMPLIDO CON TB')
        ->assertDontSee('CUMPLIDO SIN TB');
});

it('asigna el TB a ítems de distintos pendientes del mismo C.C.', function (): void {
    $first = deliveredItem();
    $second = deliveredItem();

    tbPage()
        ->set('selected', [(string) $first->id, (string) $second->id])
        ->set('docNumber', '123456')
        ->call('assign')
        ->assertHasNoErrors()
        ->assertSet('selected', []);

    expect($first->refresh()->doc_number)->toBe('123456')
        ->and($second->refresh()->doc_number)->toBe('123456');
});

it('rechaza ítems de distinto C.C., TB no numérico e ítems Parciales', function (): void {
    $a = deliveredItem('1300');
    $b = deliveredItem('1600');
    $partial = deliveredItem('1300', ['status' => PendingItemStatus::Partial, 'number_day' => null]);

    tbPage()->set('selected', [(string) $a->id, (string) $b->id])->set('docNumber', '1')->call('assign')->assertHasErrors(['docNumber']);
    tbPage()->set('selected', [(string) $a->id])->set('docNumber', 'TB-1')->call('assign')->assertHasErrors(['docNumber']);
    tbPage()->set('selected', [(string) $partial->id])->set('docNumber', '1')->call('assign')->assertHasErrors(['docNumber']);

    expect(PendingItem::whereNotNull('doc_number')->count())->toBe(0);
});

it('cambiar o quitar un TB ya asignado exige el permiso de reversar', function (): void {
    $item = deliveredItem(attributes: ['doc_number' => '111']);

    tbPage()->set('filterTb', 'all')->set('selected', [(string) $item->id])->set('docNumber', '222')
        ->call('assign')->assertHasErrors(['docNumber']);
    tbPage()->set('selected', [(string) $item->id])->call('remove')->assertForbidden();
    expect($item->refresh()->doc_number)->toBe('111');

    $reverser = ['inventory-balances-read', 'inventory-balances-process', 'inventory-balances-reverse'];
    tbPage($reverser)->set('selected', [(string) $item->id])->set('docNumber', '222')->call('assign')->assertHasNoErrors();
    expect($item->refresh()->doc_number)->toBe('222');

    tbPage($reverser)->set('selected', [(string) $item->id])->call('remove');
    expect($item->refresh()->doc_number)->toBeNull();
});

it('exige inventory-balances-read para ver y process para asignar', function (): void {
    $this->actingAs(userWith('pendings-read'))
        ->get(route('inventory-balances.transfer-documents'))
        ->assertForbidden();

    $item = deliveredItem();
    tbPage(['inventory-balances-read'])->set('selected', [(string) $item->id])->set('docNumber', '1')
        ->call('assign')->assertForbidden();
});

it('asigna el TB a todos los ítems que coinciden con el filtro de C.C., más allá de la página', function (): void {
    $items = collect(range(1, 12))->map(fn () => deliveredItem('1300'));
    deliveredItem('1600');

    $page = Livewire::actingAs(userWith(['inventory-balances-read', 'inventory-balances-process']))
        ->test('pharmacy::pages.transfer-document-index')
        ->set('filterDestination', '1300')
        ->set('perPage', 10);

    $page->call('toggleSelectAll', $page->instance()->items->pluck('id')->all())
        ->call('selectAllMatchingRows')
        ->set('docNumber', '777')
        ->call('assign')
        ->assertHasNoErrors();

    expect(PendingItem::where('doc_number', '777')->count())->toBe(12)
        ->and(PendingItem::where('destination_code', '1600')->value('doc_number'))->toBeNull();
});
