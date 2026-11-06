<?php

use App\Actions\Imports\ImportRows;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Imports\ProductImporter;
use Modules\Shopping\App\Models\Product;

/** Archivo CSV de productos con los encabezados de la plantilla. */
function productsCsv(array $lines): UploadedFile
{
    $header = 'item,description,brand,ATC,measure_unit,generic_name,generic_code,tax_percentage,status,created_by';

    return UploadedFile::fake()->createWithContent('productos.csv', implode("\n", [$header, ...$lines]));
}

it('crea productos nuevos y actualiza los existentes por ítem', function () {
    $existing = Product::factory()->create(['item' => '100', 'description' => 'ANTERIOR', 'brand' => 'MARCA VIEJA']);

    $result = app(ImportRows::class)->execute(new ProductImporter, productsCsv([
        '100,ACETAMINOFEN 500MG,GENFAR,N02BE01,TAB,ACETAMINOFEN,7,NULL,,1',
        '200,IBUPROFENO 400MG,MK,M01AE01,TAB,IBUPROFENO,9,19,Inactivo,1',
    ]));

    expect($result->failed())->toBeFalse()
        ->and($result->created)->toBe(1)
        ->and($result->updated)->toBe(1);

    $existing->refresh();
    expect($existing->description)->toBe('ACETAMINOFEN 500MG')
        ->and($existing->atc)->toBe('N02BE01')
        ->and($existing->tax_percentage)->toBeNull()
        ->and($existing->status)->toBe(ProductStatus::Active);

    expect(Product::firstWhere('item', '200')->status)->toBe(ProductStatus::Inactive);
});

it('no guarda nada si alguna fila tiene errores y reporta el número de fila', function () {
    $result = app(ImportRows::class)->execute(new ProductImporter, productsCsv([
        '300,VALIDO,MK,,TAB,GENERICO,1,,,1',
        '301,,MK,,TAB,GENERICO,1,,,1',
        '300,REPETIDO,MK,,TAB,GENERICO,1,,,1',
    ]));

    expect($result->failed())->toBeTrue()
        ->and($result->errors)->toHaveCount(2)
        ->and($result->errors[0])->toStartWith('Fila 3:')
        ->and($result->errors[1])->toStartWith('Fila 4:')->toContain('fila 2');

    expect(Product::count())->toBe(0);
});

it('rechaza un archivo sin la columna clave', function () {
    $file = UploadedFile::fake()->createWithContent('productos.csv', "description\nSIN ITEM");

    $result = app(ImportRows::class)->execute(new ProductImporter, $file);

    expect($result->errors[0])->toContain('"item"');
});

it('importa desde el listado y cierra el modal', function () {
    Livewire::actingAs(actingAsAdmin())
        ->test('shopping::pages.product-index')
        ->call('openImport')
        ->assertSet('showImport', true)
        ->set('importFile', productsCsv(['400,LORATADINA 10MG,MK,,TAB,LORATADINA,5,,,1']))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('showImport', false)
        ->assertSet('importErrors', []);

    expect(Product::where('item', '400')->exists())->toBeTrue();
});

it('muestra los errores de fila en el modal', function () {
    Livewire::actingAs(actingAsAdmin())
        ->test('shopping::pages.product-index')
        ->call('openImport')
        ->set('importFile', productsCsv(['500,,MK,,TAB,GENERICO,1,,,1']))
        ->call('import')
        ->assertSet('showImport', true)
        ->assertCount('importErrors', 1);

    expect(Product::count())->toBe(0);
});

it('exige el permiso products-import', function () {
    Livewire::actingAs(userWith(['products-read', 'products-create', 'products-update']))
        ->test('shopping::pages.product-index')
        ->call('openImport')
        ->assertForbidden();
});

it('descarga la plantilla con los encabezados del importador', function () {
    Livewire::actingAs(userWith(['products-read', 'products-import']))
        ->test('shopping::pages.product-index')
        ->call('downloadImportTemplate')
        ->assertFileDownloaded('plantilla-product.xlsx');
});

it('importa el NIT del proveedor y rechaza valores no numéricos', function () {
    $csv = fn (string $nit) => UploadedFile::fake()->createWithContent('productos.csv', implode("\n", [
        'item,description,measure_unit,generic_name,generic_code,supplier_nit',
        "500,ACETAMINOFEN 500MG,TAB,ACETAMINOFEN,7,{$nit}",
    ]));

    $result = app(ImportRows::class)->execute(new ProductImporter, $csv('NIT-ABC'));
    expect($result->failed())->toBeTrue()->and($result->errors[0])->toStartWith('Fila 2:');

    $result = app(ImportRows::class)->execute(new ProductImporter, $csv('900123456'));
    expect($result->failed())->toBeFalse()
        ->and(Product::firstWhere('item', '500')->supplier_nit)->toBe(900123456);
});
