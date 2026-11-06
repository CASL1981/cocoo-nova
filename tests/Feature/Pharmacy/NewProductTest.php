<?php

use App\Models\ServicePoint;
use App\Models\User;
use Livewire\Livewire;
use Modules\Pharmacy\App\Enums\NewProductStatus;
use Modules\Pharmacy\App\Livewire\NewProducts\NewProductFormModal;
use Modules\Pharmacy\App\Models\NewProduct;
use Modules\Pharmacy\App\Reports\NewProductConsolidation;
use Modules\Shopping\App\Models\Product;

/** Usuario de Oficina Principal con los permisos indicados. */
function headquartersUser(array $permissions): User
{
    $headquarters = ServicePoint::query()->where('code', ServicePoint::HEADQUARTERS_CODE)->first()
        ?? ServicePoint::factory()->create(['code' => ServicePoint::HEADQUARTERS_CODE]);

    $user = userWith($permissions);
    $user->update(['service_point_id' => $headquarters->id]);

    return $user->refresh();
}

function managementPage(array $permissions = ['new-products-read', 'new-products-process'])
{
    return Livewire::actingAs(headquartersUser($permissions))->test('pharmacy::pages.new-product-management');
}

/** Solicitud de la farmacia del usuario indicado. */
function newProductFor(User $user, array $attributes = []): NewProduct
{
    return NewProduct::factory()->create(['destination_code' => $user->loadMissing('servicePoint')->servicePoint->code, ...$attributes]);
}

describe('solicitudes de las farmacias', function (): void {
    it('registra la solicitud para la sede del usuario, sin asignar y sin marca obligatoria', function (): void {
        $user = userWith(['new-products-read', 'new-products-create']);

        Livewire::actingAs($user)->test(NewProductFormModal::class)
            ->call('openCreate')
            ->assertSet('form.destination_code', $user->loadMissing('servicePoint')->servicePoint->code)
            ->set('form.product_name', 'Semaglutida 1 mg pluma')
            ->set('form.quantity', '4')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('new-product-saved');

        $newProduct = NewProduct::sole();
        expect($newProduct->status)->toBe(NewProductStatus::Unassigned)
            ->and($newProduct->user_id)->toBe($user->id)
            ->and($newProduct->brand)->toBeNull()
            ->and($newProduct->normalized_name)->toBe('SEMAGLUTIDA 1 MG PLUMA');
    });

    it('fuera de Oficina Principal no permite solicitar para otra farmacia', function (): void {
        $other = ServicePoint::factory()->create();

        Livewire::actingAs(userWith(['new-products-read', 'new-products-create']))->test(NewProductFormModal::class)
            ->call('openCreate')
            ->set('form.product_name', 'Producto X')
            ->set('form.quantity', '1')
            ->set('form.destination_code', $other->code)
            ->call('save')
            ->assertHasErrors(['form.destination_code']);
    });

    it('bloquea una solicitud abierta repetida en la misma farmacia (sin importar tildes ni mayúsculas)', function (): void {
        $user = userWith(['new-products-read', 'new-products-create']);
        newProductFor($user, ['product_name' => 'Insulina Glargina']);

        Livewire::actingAs($user)->test(NewProductFormModal::class)
            ->call('openCreate')
            ->set('form.product_name', '  insulina   GLARGINÁ ')
            ->set('form.quantity', '2')
            ->call('save')
            ->assertHasErrors(['form.product_name']);

        // En otra farmacia sí se puede solicitar.
        Livewire::actingAs(headquartersUser(['new-products-read', 'new-products-create']))->test(NewProductFormModal::class)
            ->call('openCreate')
            ->set('form.product_name', 'insulina glargina')
            ->set('form.quantity', '2')
            ->set('form.destination_code', ServicePoint::factory()->create()->code)
            ->call('save')
            ->assertHasNoErrors();

        expect(NewProduct::count())->toBe(2);
    });

    it('bloquea un nombre ya asociado a un ítem: debe pedirse por Pendientes', function (): void {
        $product = Product::factory()->create(['item' => 'PRD-77777']);
        NewProduct::factory()->linked($product)->create(['product_name' => 'Tirzepatida 5 mg']);

        Livewire::actingAs(userWith(['new-products-read', 'new-products-create']))->test(NewProductFormModal::class)
            ->call('openCreate')
            ->set('form.product_name', 'TIRZEPATIDA 5 MG')
            ->assertSee('PRD-77777')
            ->set('form.quantity', '1')
            ->call('save')
            ->assertHasErrors(['form.product_name'])
            ->assertSee('solicítelo en Pendientes');
    });

    it('solo edita o elimina solicitudes Sin asignar y sin ítem de su sede', function (): void {
        $user = userWith(['new-products-read', 'new-products-update', 'new-products-delete']);
        $own = newProductFor($user);
        $linked = NewProduct::factory()->linked()->create(['destination_code' => $user->loadMissing('servicePoint')->servicePoint->code]);
        $otherSede = NewProduct::factory()->create();

        expect($user->can('update', $own))->toBeTrue()
            ->and($user->can('update', $linked))->toBeFalse()
            ->and($user->can('update', $otherSede))->toBeFalse();

        Livewire::actingAs($user)->test('pharmacy::pages.new-product-index')
            ->set('selected', [(string) $own->id, (string) $linked->id, (string) $otherSede->id])
            ->call('deleteSelected');

        expect($own->refresh()->trashed())->toBeTrue()
            ->and($linked->refresh()->trashed())->toBeFalse()
            ->and($otherSede->refresh()->trashed())->toBeFalse();
    });

    it('exige new-products-read para ver las solicitudes', function (): void {
        $this->actingAs(userWith('pendings-read'))->get(route('new-products.index'))->assertForbidden();
        $this->actingAs(userWith('new-products-read'))->get(route('new-products.index'))->assertOk();
    });
});

describe('gestión de Oficina Principal', function (): void {
    it('solo la ven usuarios de Oficina Principal con new-products-read', function (): void {
        $this->actingAs(userWith(['new-products-read', 'new-products-process']))
            ->get(route('new-products.management'))->assertForbidden();
        $this->actingAs(headquartersUser(['new-products-read']))
            ->get(route('new-products.management'))->assertOk();
    });

    it('exige asociar el ítem antes de asignar y calcula Parcial y Cumplido', function (): void {
        $row = NewProduct::factory()->create(['quantity' => 10]);
        $product = Product::factory()->create();

        $page = managementPage()
            ->call('editRow', $row->id, 'assign')
            ->set('rowQuantity', '3')
            ->call('saveRow')
            ->assertHasErrors(['quantity']);
        expect($row->refresh()->send_quantity)->toBeNull();

        $page->set('selected', [(string) $row->id])
            ->call('selectProduct', (string) $product->id)
            ->call('link')
            ->assertHasNoErrors();
        expect($row->refresh()->product_id)->toBe($product->id);

        $page->call('editRow', $row->id, 'assign')->set('rowQuantity', '4')->call('saveRow')->assertHasNoErrors();
        expect($row->refresh())->status->toBe(NewProductStatus::Partial)->send_quantity->toBe(4);

        $page->call('editRow', $row->id, 'assign')->set('rowQuantity', '7')->call('saveRow')->assertHasErrors(['quantity']);

        $page->call('editRow', $row->id, 'assign')->set('rowQuantity', '6')->call('saveRow')->assertHasNoErrors();
        expect($row->refresh())->status->toBe(NewProductStatus::Fulfilled)->send_quantity->toBe(10)
            ->and($row->date_send)->not->toBeNull();
    });

    it('corregir el enviado exige reversar y en cero vuelve a Sin asignar', function (): void {
        $row = NewProduct::factory()->fulfilled()->create(['quantity' => 5]);

        managementPage()->call('editRow', $row->id, 'correct')->assertForbidden();

        managementPage(['new-products-read', 'new-products-reverse'])
            ->call('editRow', $row->id, 'correct')
            ->set('rowQuantity', '0')
            ->call('saveRow')
            ->assertHasNoErrors();

        expect($row->refresh())->status->toBe(NewProductStatus::Unassigned)->send_quantity->toBeNull();
    });

    it('rechaza solo solicitudes Sin asignar y reabre con reversar', function (): void {
        $unassigned = NewProduct::factory()->create();
        $fulfilled = NewProduct::factory()->fulfilled()->create();

        managementPage()->set('selected', [(string) $unassigned->id, (string) $fulfilled->id])->call('reject');
        expect($unassigned->refresh()->status)->toBe(NewProductStatus::Unassigned);

        managementPage()->set('selected', [(string) $unassigned->id])->call('reject');
        expect($unassigned->refresh()->status)->toBe(NewProductStatus::Rejected);

        managementPage()->set('filterStatus', 'all')->set('selected', [(string) $unassigned->id])->call('reopen')->assertForbidden();
        managementPage(['new-products-read', 'new-products-reverse'])->set('filterStatus', 'all')
            ->set('selected', [(string) $unassigned->id])->call('reopen');
        expect($unassigned->refresh()->status)->toBe(NewProductStatus::Unassigned);
    });

    it('asigna TB solo a Cumplidas de un mismo C.C.; cambiarlo exige reversar', function (): void {
        $a = NewProduct::factory()->fulfilled()->create(['destination_code' => '1300']);
        $b = NewProduct::factory()->fulfilled()->create(['destination_code' => '1300']);
        $otherSede = NewProduct::factory()->fulfilled()->create(['destination_code' => '1600']);
        $partial = NewProduct::factory()->linked()->create(['destination_code' => '1300', 'send_quantity' => 1, 'quantity' => 5, 'status' => NewProductStatus::Partial]);

        managementPage()->set('filterStatus', 'all')->set('selected', [(string) $a->id, (string) $otherSede->id])
            ->set('docNumber', '10')->call('assignTransferDocument')->assertHasErrors(['docNumber']);
        managementPage()->set('selected', [(string) $partial->id])
            ->set('docNumber', '10')->call('assignTransferDocument')->assertHasErrors(['docNumber']);

        managementPage()->set('filterStatus', 'all')->set('selected', [(string) $a->id, (string) $b->id])
            ->set('docNumber', '123')->call('assignTransferDocument')->assertHasNoErrors();
        expect($a->refresh()->doc_number)->toBe('123')->and($b->refresh()->doc_number)->toBe('123');

        managementPage()->set('filterStatus', 'all')->set('selected', [(string) $a->id])
            ->set('docNumber', '999')->call('assignTransferDocument')->assertHasErrors(['docNumber']);
        managementPage(['new-products-read', 'new-products-process', 'new-products-reverse'])->set('filterStatus', 'all')
            ->set('selected', [(string) $a->id])->set('docNumber', '999')->call('assignTransferDocument')->assertHasNoErrors();
        expect($a->refresh()->doc_number)->toBe('999');
    });
});

it('consolida lo que falta enviar de productos nuevos abiertos por producto y farmacia', function (): void {
    $valencia = ServicePoint::factory()->create(['code' => '1100', 'abbreviation' => 'VALENCIA']);
    $antero = ServicePoint::factory()->create(['code' => '1600', 'abbreviation' => 'SAN ANTERO']);

    NewProduct::factory()->create(['product_name' => 'Ozempic 1 mg', 'brand' => 'Novo', 'destination_code' => $valencia->code, 'quantity' => 3]);
    NewProduct::factory()->linked()->create(['product_name' => 'OZEMPIC 1 MG', 'brand' => 'Novo', 'destination_code' => $antero->code,
        'quantity' => 5, 'send_quantity' => 2, 'status' => NewProductStatus::Partial]);
    // Excluidas: cumplida y rechazada.
    NewProduct::factory()->fulfilled()->create(['destination_code' => $valencia->code]);
    NewProduct::factory()->create(['destination_code' => $antero->code, 'status' => NewProductStatus::Rejected]);

    $report = app(NewProductConsolidation::class)->build();

    expect($report['columns'])->toBe([1100 => 'VALENCIA', 1600 => 'SAN ANTERO'])
        ->and($report['rows'])->toBe([
            ['product' => 'OZEMPIC 1 MG · Novo', 'quantities' => [1100 => 3, 1600 => 3], 'total' => 6],
        ]);

    Livewire::actingAs(userWith('inventory-balances-read'))
        ->test('pharmacy::pages.pending-consolidation')
        ->assertSee('Productos nuevos')
        ->assertSee('OZEMPIC 1 MG · Novo')
        ->call('export')
        ->assertFileDownloaded();
});
