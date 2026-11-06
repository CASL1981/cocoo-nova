<?php

use Livewire\Livewire;
use Modules\Pharmacy\App\Models\StockOut;

it('oculta y vuelve a mostrar una columna del listado', function (): void {
    StockOut::factory()->create();

    Livewire::actingAs(actingAsAdmin())
        ->test('pharmacy::pages.stock-out-index')
        ->assertSeeHtml('aria-label="Configurar columnas"')
        ->call('toggleColumn', 'generic_code')
        ->assertSet('hiddenColumns', ['generic_code'])
        ->assertSeeHtml(':not([colspan]) { display: none; }')
        ->call('toggleColumn', 'generic_code')
        ->assertSet('hiddenColumns', [])
        ->assertDontSeeHtml(':not([colspan]) { display: none; }');
});

it('al ocultar una columna limpia su filtro y ignora columnas inexistentes', function (): void {
    Livewire::actingAs(actingAsAdmin())
        ->test('pharmacy::pages.stock-out-index')
        ->set('filterGenericCode', 'ABC')
        ->call('toggleColumn', 'generic_code')
        ->assertSet('filterGenericCode', '')
        ->call('toggleColumn', 'no_existe')
        ->assertSet('hiddenColumns', ['generic_code'])
        ->call('showAllColumns')
        ->assertSet('hiddenColumns', []);
});

it('la barra de acciones conserva permisos y el menú Más', function (): void {
    Livewire::actingAs(userWith(['stock-outs-read']))
        ->test('pharmacy::pages.stock-out-index')
        ->assertDontSeeHtml("\$dispatch('open-stock-out-create')")
        ->assertDontSeeHtml('$wire.deleteSelected()')
        ->assertSeeHtml('table-actions-compact')
        ->assertSeeHtml("export('xlsx')");
});

it('aplica ancho fijo con recorte a las columnas con width en columns()', function (): void {
    Livewire::actingAs(actingAsAdmin())
        ->test('pharmacy::pages.stock-out-index')
        ->assertSeeHtml('max-width: 20rem')
        ->assertSeeHtml('text-overflow: ellipsis')
        ->call('toggleColumn', 'product_name')
        ->assertDontSeeHtml('max-width: 20rem');
});
