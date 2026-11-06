<?php

use App\Actions\Imports\ImportRows;
use App\Enums\Status;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Modules\Pharmacy\App\Imports\StockOutImporter;
use Modules\Pharmacy\App\Models\StockOut;

/** Archivo CSV de agotados con los encabezados de la plantilla. */
function stockOutsCsv(array $lines): UploadedFile
{
    $header = 'generic_code,generic_name,product_name,brand,classification,circular_number,circular_date,status';

    return UploadedFile::fake()->createWithContent('agotados.csv', implode("\n", [$header, ...$lines]));
}

it('crea agotados nuevos y actualiza los existentes por código genérico', function (): void {
    $existing = StockOut::factory()->create(['generic_code' => 'G100', 'circular_number' => 'C-OLD']);

    $result = app(ImportRows::class)->execute(new StockOutImporter, stockOutsCsv([
        'G100,ACETAMINOFEN,ACETAMINOFEN 500MG,GENFAR,,C-NEW,15/09/2026,',
        'G200,IBUPROFENO,IBUPROFENO 400MG,MK,,C-200,2026-09-01,Inactivo',
    ]));

    expect($result->failed())->toBeFalse()
        ->and($result->created)->toBe(1)
        ->and($result->updated)->toBe(1);

    $existing->refresh();
    expect($existing->circular_number)->toBe('C-NEW')
        ->and($existing->circular_date->format('Y-m-d'))->toBe('2026-09-15');

    expect(StockOut::firstWhere('generic_code', 'G200')->status)->toBe(Status::Inactive);
});

it('no guarda nada si alguna fila tiene errores', function (): void {
    $result = app(ImportRows::class)->execute(new StockOutImporter, stockOutsCsv([
        'G300,GENERICO,PRODUCTO,,,C-1,2026-09-01,',
        'G301,,PRODUCTO,,,C-1,2026-09-01,',
        'G302,GENERICO,PRODUCTO,,NO EXISTE,C-1,2026-09-01,',
    ]));

    expect($result->failed())->toBeTrue()
        ->and($result->errors[0])->toStartWith('Fila 3:')
        ->and($result->errors[1])->toStartWith('Fila 4:');

    expect(StockOut::count())->toBe(0);
});

it('importa desde el listado de agotados', function (): void {
    Livewire::actingAs(userWith(['stock-outs-read', 'stock-outs-import']))
        ->test('pharmacy::pages.stock-out-index')
        ->call('openImport')
        ->set('importFile', stockOutsCsv(['G400,LORATADINA,LORATADINA 10MG,MK,,C-400,2026-09-01,']))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('showImport', false);

    expect(StockOut::where('generic_code', 'G400')->exists())->toBeTrue();
});

it('exige el permiso stock-outs-import', function (): void {
    Livewire::actingAs(userWith(['stock-outs-read', 'stock-outs-create']))
        ->test('pharmacy::pages.stock-out-index')
        ->call('openImport')
        ->assertForbidden();
});
