<?php

use App\Actions\Imports\ImportRows;
use App\Enums\Status;
use App\Models\ServicePoint;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Imports\PendingItemImporter;
use Modules\Pharmacy\App\Livewire\Pendings\PendingItemsImport;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;
use Modules\Pharmacy\App\Models\StockOut;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Models\Product;

/** Archivo CSV con el formato de doc/detallepedido.xlsx. */
function pendingItemsCsv(array $lines): UploadedFile
{
    $header = 'item,idpendiente,nombreproducto,marca,centrocosto,cantidad';

    return UploadedFile::fake()->createWithContent('detallepedido.csv', implode("\n", [$header, ...$lines]));
}

/** Usuario de farmacia con permisos de editar e importar y un pendiente activo de su sede. */
function importingUserWithPending(): array
{
    $user = userWith(['pendings-read', 'pendings-update', 'pendings-import']);
    $pending = Pending::factory()->for($user)->create(['destination_id' => $user->service_point_id]);

    return [$user, $pending, $user->loadMissing('servicePoint')->servicePoint->code];
}

it('crea los ítems del pendiente abierto con el snapshot del catálogo', function (): void {
    [$user, $pending, $code] = importingUserWithPending();
    $product = Product::factory()->create(['item' => '1001', 'generic_name' => 'ACETAMINOFEN', 'generic_code' => '3099']);
    Product::factory()->create(['item' => '1002', 'generic_name' => 'IBUPROFENO', 'generic_code' => '4000']);
    StockOut::factory()->create(['generic_code' => '4000', 'circular_number' => 'C-9', 'status' => Status::Active]);

    $result = app(ImportRows::class)->execute(new PendingItemImporter($pending, $user), pendingItemsCsv([
        "1001,{$pending->id},TEXTO INFORMATIVO,GENFAR,{$code},10",
        "1002,{$pending->id},,,{$code},5",
    ]));

    expect($result->errors)->toBe([])
        ->and($result->created)->toBe(2);

    $items = $pending->items()->orderBy('id')->get();
    expect($items[0]->product_id)->toBe($product->id)
        ->and($items[0]->generic_name)->toBe('ACETAMINOFEN')
        ->and($items[0]->brand)->toBe('GENFAR')
        ->and($items[0]->quantity)->toBe(10)
        ->and($items[0]->status)->toBe(PendingItemStatus::Unreviewed)
        ->and($items[1]->status)->toBe(PendingItemStatus::OutOfStock)
        ->and($items[1]->circular_number)->toBe('C-9');
});

it('rechaza filas de otro pendiente, ítems inexistentes o inactivos y C.C. vacío', function (): void {
    [$user, $pending, $code] = importingUserWithPending();
    Product::factory()->create(['item' => '1001']);
    Product::factory()->create(['item' => '1003', 'status' => ProductStatus::Inactive]);

    $result = app(ImportRows::class)->execute(new PendingItemImporter($pending, $user), pendingItemsCsv([
        "1001,999999,,,{$code},1",
        "9999,{$pending->id},,,{$code},1",
        "1003,{$pending->id},,,{$code},1",
        "1001,{$pending->id},,,,1",
    ]));

    expect($result->errors)->toHaveCount(4)
        ->and($result->errors[0])->toStartWith('Fila 2:')->toContain((string) $pending->id)
        ->and($result->errors[1])->toStartWith('Fila 3:')
        ->and($result->errors[2])->toStartWith('Fila 4:')
        ->and($result->errors[3])->toStartWith('Fila 5:');
    expect(PendingItem::count())->toBe(0);
});

it('rechaza genéricos repetidos en el pendiente o en el archivo para el mismo C.C.', function (): void {
    [$user, $pending, $code] = importingUserWithPending();
    Product::factory()->create(['item' => '1001', 'generic_name' => 'ACETAMINOFEN', 'generic_code' => '3099']);
    Product::factory()->create(['item' => '1002', 'generic_name' => 'ACETAMINOFEN', 'generic_code' => '3099']);
    Product::factory()->create(['item' => '2001', 'generic_name' => 'LORATADINA', 'generic_code' => '5000']);
    PendingItem::factory()->for($pending)->create([
        'generic_name' => 'LORATADINA', 'generic_code' => '5000', 'destination_code' => $code,
    ]);

    $result = app(ImportRows::class)->execute(new PendingItemImporter($pending, $user), pendingItemsCsv([
        "1001,{$pending->id},,,{$code},1",
        "1002,{$pending->id},,,{$code},1",
        "2001,{$pending->id},,,{$code},1",
    ]));

    expect($result->errors)->toHaveCount(2)
        ->and($result->errors[0])->toStartWith('Fila 3:')->toContain('repetido')
        ->and($result->errors[1])->toStartWith('Fila 4:')->toContain('ya está registrado');
    expect($pending->items()->count())->toBe(1);
});

it('solo Oficina Principal registra un C.C. distinto al del pendiente', function (): void {
    [$user, $pending] = importingUserWithPending();
    Product::factory()->create(['item' => '1001']);
    $other = ServicePoint::factory()->create();

    $result = app(ImportRows::class)->execute(new PendingItemImporter($pending, $user), pendingItemsCsv([
        "1001,{$pending->id},,,{$other->code},1",
    ]));

    expect($result->errors[0])->toContain('Solo Oficina Principal');
});

it('importa desde la pantalla del pendiente', function (): void {
    [$user, $pending, $code] = importingUserWithPending();
    Product::factory()->create(['item' => '1001']);

    Livewire::actingAs($user)
        ->test('pharmacy::pages.pending-show', ['pending' => $pending])
        ->call('openImport')
        ->set('importFile', pendingItemsCsv(["1001,{$pending->id},,,{$code},3"]))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('showImport', false);

    expect($pending->items()->count())->toBe(1);
});

it('exige el permiso pendings-import', function (): void {
    $user = userWith(['pendings-read', 'pendings-update']);
    $pending = Pending::factory()->for($user)->create(['destination_id' => $user->service_point_id]);

    Livewire::actingAs($user)
        ->test('pharmacy::pages.pending-show', ['pending' => $pending])
        ->call('openImport')
        ->assertForbidden();
});

it('no permite importar en un pendiente terminado', function (): void {
    $user = userWith(['pendings-read', 'pendings-update', 'pendings-import']);
    $pending = Pending::factory()->finished()->for($user)->create(['destination_id' => $user->service_point_id]);

    Livewire::actingAs($user)
        ->test('pharmacy::pages.pending-show', ['pending' => $pending])
        ->call('openImport')
        ->assertForbidden();
});

/** Usuario de Oficina Principal con permisos de editar e importar pendientes. */
function headquartersImporter(): User
{
    $headquarters = ServicePoint::query()->firstWhere('code', ServicePoint::HEADQUARTERS_CODE)
        ?? ServicePoint::factory()->create(['code' => ServicePoint::HEADQUARTERS_CODE]);

    return User::factory()->create(['service_point_id' => $headquarters->id])
        ->givePermissionTo(['pendings-read', 'pendings-update', 'pendings-import']);
}

it('Oficina Principal importa ítems a varios pendientes activos desde un archivo', function (): void {
    $user = headquartersImporter();
    $first = Pending::factory()->create();
    $second = Pending::factory()->create();
    Product::factory()->create(['item' => '1001', 'generic_name' => 'ACETAMINOFEN', 'generic_code' => '3099']);
    $codeA = $first->loadMissing('destination')->destination->code;
    $codeB = $second->loadMissing('destination')->destination->code;

    $result = app(ImportRows::class)->execute(new PendingItemImporter(null, $user), pendingItemsCsv([
        "1001,{$first->id},,,{$codeA},2",
        "1001,{$second->id},,,{$codeB},3",
    ]));

    expect($result->errors)->toBe([])
        ->and($first->items()->sole()->quantity)->toBe(2)
        ->and($second->items()->sole()->quantity)->toBe(3);
});

it('rechaza filas de pendientes terminados, inexistentes o genéricos repetidos en el mismo pendiente', function (): void {
    $user = headquartersImporter();
    $active = Pending::factory()->create();
    $finished = Pending::factory()->finished()->create();
    Product::factory()->create(['item' => '1001', 'generic_name' => 'ACETAMINOFEN', 'generic_code' => '3099']);
    $code = $active->loadMissing('destination')->destination->code;

    $result = app(ImportRows::class)->execute(new PendingItemImporter(null, $user), pendingItemsCsv([
        "1001,{$active->id},,,{$code},1",
        "1001,{$finished->id},,,{$code},1",
        "1001,999999,,,{$code},1",
        "1001,{$active->id},,,{$code},1",
    ]));

    expect($result->errors)->toHaveCount(3)
        ->and($result->errors[0])->toStartWith('Fila 3:')->toContain("pendiente {$finished->id}")
        ->and($result->errors[1])->toStartWith('Fila 4:')->toContain('999999')
        ->and($result->errors[2])->toStartWith('Fila 5:')->toContain('repetido');
    expect(PendingItem::count())->toBe(0);
});

it('importa ítems a varios pendientes desde el listado', function (): void {
    $user = headquartersImporter();
    $pending = Pending::factory()->create();
    Product::factory()->create(['item' => '1001']);
    $code = $pending->loadMissing('destination')->destination->code;

    Livewire::actingAs($user)
        ->test(PendingItemsImport::class)
        ->assertSee('Importar ítems a varios pendientes')
        ->call('openImport')
        ->set('importFile', pendingItemsCsv(["1001,{$pending->id},,,{$code},4"]))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('showImport', false);

    expect($pending->items()->sole()->quantity)->toBe(4);
});

it('solo Oficina Principal ve e importa ítems a varios pendientes', function (): void {
    [$user] = importingUserWithPending();

    Livewire::actingAs($user)
        ->test(PendingItemsImport::class)
        ->assertDontSee('Importar ítems a varios pendientes')
        ->call('openImport')
        ->assertForbidden();
});
