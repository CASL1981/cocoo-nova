<?php

use App\Models\ServicePoint;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;
use Modules\Pharmacy\App\Models\StockOut;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Models\Product;

/** Usuario de farmacia con permiso de editar pendientes y un pendiente activo de su sede. */
function pharmacyUserWithPending(): array
{
    $user = userWith(['pendings-read', 'pendings-update']);
    $pending = Pending::factory()->for($user)->create(['destination_id' => $user->service_point_id]);

    return [$user, $pending];
}

function itemsPage(User $user, Pending $pending): Testable
{
    return Livewire::actingAs($user)->test('pharmacy::pages.pending-show', ['pending' => $pending]);
}

it('niega la pantalla de ítems sin permiso de lectura', function (): void {
    $pending = Pending::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('pendings.show', $pending))
        ->assertForbidden();
});

it('muestra los ítems del pendiente con permiso de lectura', function (): void {
    $item = PendingItem::factory()->create(['generic_name' => 'ACETAMINOFEN 150MG/5ML JBE']);

    $this->actingAs(userWith('pendings-read'))
        ->get(route('pendings.show', $item->pending_id))
        ->assertOk()
        ->assertSee('ACETAMINOFEN 150MG/5ML JBE');
});

it('busca productos activos por nombre genérico', function (): void {
    [$user, $pending] = pharmacyUserWithPending();
    $match = Product::factory()->create(['generic_name' => 'ACETAMINOFEN', 'description' => 'ACETAMINOFEN JBE']);
    Product::factory()->create(['generic_name' => 'ACETAMINOFEN', 'status' => ProductStatus::Inactive]);
    Product::factory()->create(['generic_name' => 'IBUPROFENO', 'description' => 'ACETAMINOFEN EN DESCRIPCION']);

    $results = itemsPage($user, $pending)->set('productSearch', 'acetam')->instance()->productResults;

    expect(array_keys($results))->toBe([$match->id]);
});

it('agrupa las presentaciones de un mismo genérico y distingue los nombres con varios códigos', function (): void {
    [$user, $pending] = pharmacyUserWithPending();
    $first = Product::factory()->create(['generic_name' => 'ACETAMINOFEN 500MG TAB', 'generic_code' => '3099']);
    Product::factory()->create(['generic_name' => 'ACETAMINOFEN 500MG TAB', 'generic_code' => '3099']);
    $syrupA = Product::factory()->create(['generic_name' => 'ACETAMINOFEN JBE', 'generic_code' => '3280']);
    $syrupB = Product::factory()->create(['generic_name' => 'ACETAMINOFEN JBE', 'generic_code' => '3473']);

    $results = itemsPage($user, $pending)->set('productSearch', 'acetaminofen')->instance()->productResults;

    expect($results)->toBe([
        $first->id => 'ACETAMINOFEN 500MG TAB',
        $syrupA->id => 'ACETAMINOFEN JBE · Cód. 3280',
        $syrupB->id => 'ACETAMINOFEN JBE · Cód. 3473',
    ]);
});

it('agrega un ítem con snapshot del producto, marca escrita y el C.C. del encabezado', function (): void {
    [$user, $pending] = pharmacyUserWithPending();
    $product = Product::factory()->create(['brand' => 'MK']);

    itemsPage($user, $pending)
        ->call('selectProduct', (string) $product->id)
        ->set('form.brand', 'GENFAR')
        ->set('form.quantity', '10')
        ->call('save')
        ->assertHasNoErrors();

    $item = PendingItem::sole();
    expect($item->pending_id)->toBe($pending->id)
        ->and($item->product_name)->toBe($product->description)
        ->and($item->generic_name)->toBe($product->generic_name)
        ->and($item->generic_code)->toBe((string) $product->generic_code)
        ->and($item->brand)->toBe('GENFAR')
        ->and($item->destination_code)->toBe($user->servicePoint->code)
        ->and($item->status)->toBe(PendingItemStatus::Unreviewed);
});

it('marca como agotado el ítem cuyo genérico tiene un agotado activo', function (): void {
    [$user, $pending] = pharmacyUserWithPending();
    $product = Product::factory()->create();
    StockOut::factory()->create(['generic_code' => $product->generic_code, 'circular_number' => 'C-2026-15']);

    itemsPage($user, $pending)
        ->call('selectProduct', (string) $product->id)
        ->set('form.quantity', '5')
        ->call('save')
        ->assertHasNoErrors();

    expect(PendingItem::sole())
        ->status->toBe(PendingItemStatus::OutOfStock)
        ->circular_number->toBe('C-2026-15');
});

it('no repite un producto en el mismo pendiente y C.C.', function (): void {
    [$user, $pending] = pharmacyUserWithPending();
    $product = Product::factory()->create();
    PendingItem::factory()->for($pending)->for($product)
        ->create(['generic_name' => $product->generic_name, 'generic_code' => $product->generic_code]);
    // Otra presentación del mismo genérico también cuenta como repetida.
    $otherPresentation = Product::factory()->create(['generic_name' => $product->generic_name, 'generic_code' => $product->generic_code]);

    itemsPage($user, $pending)
        ->call('selectProduct', (string) $otherPresentation->id)
        ->set('form.quantity', '3')
        ->call('save')
        ->assertHasErrors(['form.product_id']);

    expect(PendingItem::count())->toBe(1);
});

it('impide que una sede registre un C.C. distinto al del encabezado', function (): void {
    [$user, $pending] = pharmacyUserWithPending();
    $other = ServicePoint::factory()->create();

    itemsPage($user, $pending)
        ->call('selectProduct', (string) Product::factory()->create()->id)
        ->set('form.quantity', '3')
        ->set('form.destination_code', $other->code)
        ->call('save')
        ->assertHasErrors(['form.destination_code']);
});

it('permite a Oficina Principal repetir un producto con C.C. distintos escritos por código', function (): void {
    $headquarters = ServicePoint::factory()->create(['code' => ServicePoint::HEADQUARTERS_CODE]);
    $user = userWith(['pendings-read', 'pendings-update']);
    $user->update(['service_point_id' => $headquarters->id]);
    $pending = Pending::factory()->for($user)->create(['destination_id' => $headquarters->id]);
    $product = Product::factory()->create();
    $branch = ServicePoint::factory()->create();

    $page = itemsPage($user->fresh(), $pending);

    foreach ([$headquarters->code, $branch->code] as $code) {
        $page->call('selectProduct', (string) $product->id)
            ->set('form.quantity', '2')
            ->set('destinationSearch', $code)
            ->call('save')
            ->assertHasNoErrors();
    }

    expect(PendingItem::pluck('destination_code')->sort()->values()->all())
        ->toBe(collect([$headquarters->code, $branch->code])->sort()->values()->all());
});

it('rechaza un C.C. que no corresponde a una sede activa', function (): void {
    $headquarters = ServicePoint::factory()->create(['code' => ServicePoint::HEADQUARTERS_CODE]);
    $user = userWith(['pendings-read', 'pendings-update']);
    $user->update(['service_point_id' => $headquarters->id]);
    $pending = Pending::factory()->create(['destination_id' => $headquarters->id]);

    itemsPage($user->fresh(), $pending)
        ->call('selectProduct', (string) Product::factory()->create()->id)
        ->set('form.quantity', '2')
        ->set('destinationSearch', '0000')
        ->call('save')
        ->assertHasErrors(['form.destination_code']);
});

it('edita la marca y la cantidad de un ítem', function (): void {
    [$user, $pending] = pharmacyUserWithPending();
    $product = Product::factory()->create();
    $item = PendingItem::factory()->for($pending)->for($product)->create(['quantity' => 1]);

    itemsPage($user, $pending)
        ->call('editItem', $item->id)
        ->assertSet('form.product_id', (string) $product->id)
        ->set('form.brand', 'LAPROFF')
        ->set('form.quantity', '25')
        ->call('save')
        ->assertHasNoErrors();

    expect($item->fresh())->brand->toBe('LAPROFF')->quantity->toBe(25);
});

it('no cambia el producto al editar un ítem', function (): void {
    [$user, $pending] = pharmacyUserWithPending();
    $product = Product::factory()->create();
    $item = PendingItem::factory()->for($pending)->for($product)->create(['generic_name' => 'ORIGINAL']);

    itemsPage($user, $pending)
        ->call('editItem', $item->id)
        ->assertSee('ORIGINAL')
        ->set('form.product_id', (string) Product::factory()->create()->id)
        ->set('form.quantity', '7')
        ->call('save')
        ->assertHasNoErrors();

    expect($item->fresh())
        ->product_id->toBe($product->id)
        ->generic_name->toBe('ORIGINAL')
        ->quantity->toBe(7);
});

it('no edita un ítem agotado pero sí lo elimina', function (): void {
    [$user, $pending] = pharmacyUserWithPending();
    $item = PendingItem::factory()->for($pending)->outOfStock()->create();

    itemsPage($user, $pending)->call('editItem', $item->id)->assertForbidden();

    itemsPage($user, $pending)->call('deleteItem', $item->id);

    // Eliminación definitiva: el ítem no queda en la base de datos.
    expect($item->fresh())->toBeNull();
});

it('no agrega, edita ni elimina ítems de un pendiente terminado', function (): void {
    [$user, $pending] = pharmacyUserWithPending();
    $item = PendingItem::factory()->for($pending)->create();
    $pending->finish();

    itemsPage($user, $pending)
        ->call('selectProduct', (string) Product::factory()->create()->id)
        ->set('form.quantity', '1')
        ->call('save')
        ->assertForbidden();

    itemsPage($user, $pending)->call('editItem', $item->id)->assertForbidden();
    itemsPage($user, $pending)->call('deleteItem', $item->id)->assertForbidden();

    expect(PendingItem::count())->toBe(1);
});

it('no permite gestionar ítems de pendientes de otra sede ni sin permiso de editar', function (): void {
    $pending = Pending::factory()->create();
    $item = PendingItem::factory()->for($pending)->create();

    $otherBranch = userWith(['pendings-read', 'pendings-update']);
    itemsPage($otherBranch, $pending)->call('deleteItem', $item->id)->assertForbidden();

    $reader = userWith('pendings-read');
    $reader->update(['service_point_id' => $pending->destination_id]);
    itemsPage($reader->fresh(), $pending)->call('deleteItem', $item->id)->assertForbidden();

    expect($item->fresh())->not->toBeNull();
});
