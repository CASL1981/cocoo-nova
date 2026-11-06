<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Modules\Pharmacy\App\Actions\ImportInventoryBalances;
use Modules\Pharmacy\App\Models\InventoryBalance;
use Modules\Shopping\App\Models\Product;

/** Archivo CSV con las columnas del ERP (ispl) que se leen y una que se ignora. */
function isplCsv(array $lines): UploadedFile
{
    return UploadedFile::fake()->createWithContent('ispl.csv', implode("\n", ['ProductId,ProductName,BalanceQuantity,Disponible', ...$lines]));
}

it('suma el disponible por ítem, homologa con el catálogo y descarta los ítems en cero', function (): void {
    $user = userWith(['inventory-balances-read', 'inventory-balances-import']);
    $product = Product::factory()->create(['item' => '60', 'generic_code' => 'G60']);

    $result = app(ImportInventoryBalances::class)->execute(isplCsv([
        '60,ADAPTADOR,10,10',
        '60,ADAPTADOR,40,32',
        '999,NO CATALOGO,5,5',
        '70,SIN EXISTENCIA,3,0',
    ]), CarbonImmutable::parse('2026-10-18'), $user);

    expect($result->errors)->toBe([])
        ->and($result->created)->toBe(2);

    $matched = InventoryBalance::firstWhere('item', '60');
    expect($matched->available_quantity)->toBe(42)
        ->and($matched->product_id)->toBe($product->id)
        ->and($matched->generic_code)->toBe('G60')
        ->and($matched->cut_off_date->format('Y-m-d'))->toBe('2026-10-18')
        ->and($matched->created_by)->toBe($user->id);

    expect(InventoryBalance::unmatched()->pluck('item')->all())->toBe(['999']);
});

it('reemplaza la carga anterior', function (): void {
    $user = userWith(['inventory-balances-import']);
    InventoryBalance::factory()->create(['item' => '1', 'assigned_quantity' => 5]);

    app(ImportInventoryBalances::class)->execute(isplCsv(['2,OTRO,8,8']), CarbonImmutable::today(), $user);

    expect(InventoryBalance::pluck('item')->all())->toBe(['2']);
});

it('no carga nada si alguna fila es inválida', function (): void {
    InventoryBalance::factory()->create(['item' => '1']);

    $result = app(ImportInventoryBalances::class)->execute(isplCsv(['2,OTRO,8,8', 'ABC,MALO,1,-1']), CarbonImmutable::today(), User::factory()->create());

    expect($result->errors)->toHaveCount(2)
        ->and($result->errors[0])->toStartWith('Fila 3:');
    expect(InventoryBalance::pluck('item')->all())->toBe(['1']);
});

it('rechaza un archivo sin las columnas del ERP', function (): void {
    $file = UploadedFile::fake()->createWithContent('otro.csv', "item,cantidad\n1,2");

    $result = app(ImportInventoryBalances::class)->execute($file, CarbonImmutable::today(), User::factory()->create());

    expect($result->errors[0])->toContain('ProductId');
});

it('carga desde la pantalla y muestra homologados y no homologados por separado', function (): void {
    Product::factory()->create(['item' => '60', 'description' => 'X', 'generic_code' => 'G60']);

    Livewire::actingAs(userWith(['inventory-balances-read', 'inventory-balances-import']))
        ->test('pharmacy::pages.inventory-balance-index')
        ->call('openImport')
        ->set('importFile', isplCsv(['60,ADAPTADOR OXIGENO,10,10', '999,PRODUCTO HUERFANO,5,5']))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('showImport', false)
        ->assertSee('ADAPTADOR OXIGENO')
        ->assertSee('PRODUCTO HUERFANO');

    expect(InventoryBalance::count())->toBe(2);
});

it('no acepta una fecha de corte futura', function (): void {
    Livewire::actingAs(userWith(['inventory-balances-read', 'inventory-balances-import']))
        ->test('pharmacy::pages.inventory-balance-index')
        ->call('openImport')
        ->set('cutOffDate', now()->addDay()->toDateString())
        ->set('importFile', isplCsv(['60,A,1,1']))
        ->call('import')
        ->assertHasErrors(['cutOffDate']);
});

it('exige el permiso de lectura para la pantalla y el de importación para cargar', function (): void {
    $this->actingAs(userWith('pendings-read'))
        ->get(route('inventory-balances.index'))
        ->assertForbidden();

    Livewire::actingAs(userWith('inventory-balances-read'))
        ->test('pharmacy::pages.inventory-balance-index')
        ->call('openImport')
        ->assertForbidden();
});

it('pagina de forma compacta: primera, vecinas de la actual y última, sin el resumen en inglés', function (): void {
    InventoryBalance::factory()->count(80)->create();

    Livewire::actingAs(userWith('inventory-balances-read'))
        ->test('pharmacy::pages.inventory-balance-index')
        ->assertSeeHtml("gotoPage(2, 'unmatchedPage')")
        ->assertDontSeeHtml("gotoPage(3, 'unmatchedPage')")
        ->assertSeeHtml("gotoPage(8, 'unmatchedPage')")
        ->assertSee('Mostrando 1–10 de 80 registros')
        ->assertDontSee('Showing');
});
