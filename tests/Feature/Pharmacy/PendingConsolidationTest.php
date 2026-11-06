<?php

use App\Models\ServicePoint;
use Livewire\Livewire;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;
use Modules\Pharmacy\App\Reports\PendingConsolidation;

/** Ítem de un pendiente terminado para la sede y el genérico indicados. */
function consolidationItem(ServicePoint $point, string $generic, array $attributes = []): PendingItem
{
    return PendingItem::factory()->for(Pending::factory()->finished())->create([
        'destination_code' => $point->code, 'generic_name' => $generic, 'generic_code' => 'G-'.$generic,
        'status' => PendingItemStatus::Pending, ...$attributes,
    ]);
}

it('suma lo que falta enviar por genérico y sede, solo de Pendientes y Parciales de pendientes terminados', function (): void {
    $valencia = ServicePoint::factory()->create(['code' => '1100', 'abbreviation' => 'VALENCIA']);
    $antero = ServicePoint::factory()->create(['code' => '1600', 'abbreviation' => 'SAN ANTERO']);
    $empty = ServicePoint::factory()->create(['code' => '1300', 'abbreviation' => 'MONTELIBANO.']);

    consolidationItem($valencia, 'ACETAMINOFEN', ['quantity' => 10]);
    consolidationItem($valencia, 'ACETAMINOFEN', ['quantity' => 5, 'send_quantity' => 2, 'status' => PendingItemStatus::Partial]);
    consolidationItem($antero, 'ACETAMINOFEN', ['quantity' => 4]);
    consolidationItem($antero, 'IBUPROFENO', ['quantity' => 7]);
    // Excluidos: Cumplido, Sin revisión y pendiente activo.
    consolidationItem($empty, 'ACETAMINOFEN', ['quantity' => 9, 'send_quantity' => 9, 'status' => PendingItemStatus::Fulfilled]);
    consolidationItem($empty, 'IBUPROFENO', ['quantity' => 3, 'status' => PendingItemStatus::Unreviewed]);
    PendingItem::factory()->create(['destination_code' => $empty->code, 'status' => PendingItemStatus::Pending, 'quantity' => 8]);

    $report = app(PendingConsolidation::class)->build();

    expect($report['columns'])->toBe([1100 => 'VALENCIA', 1600 => 'SAN ANTERO'])
        ->and($report['rows'])->toBe([
            ['product' => 'ACETAMINOFEN', 'quantities' => [1100 => 13, 1600 => 4], 'total' => 17],
            ['product' => 'IBUPROFENO', 'quantities' => [1100 => 0, 1600 => 7], 'total' => 7],
        ]);
});

it('muestra el consolidado en pantalla y lo exporta a Excel', function (): void {
    $valencia = ServicePoint::factory()->create(['abbreviation' => 'VALENCIA']);
    consolidationItem($valencia, 'LORATADINA', ['quantity' => 12]);

    Livewire::actingAs(userWith('inventory-balances-read'))
        ->test('pharmacy::pages.pending-consolidation')
        ->assertSee('VALENCIA')
        ->assertSee('LORATADINA')
        ->call('export')
        ->assertFileDownloaded();
});

it('exige inventory-balances-read', function (): void {
    $this->actingAs(userWith('pendings-read'))
        ->get(route('inventory-balances.consolidation'))
        ->assertForbidden();
});
