<?php

use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Livewire\Products\ProductFormModal;
use Modules\Shopping\App\Models\Product;

it('filtra productos por descripción', function () {
    Product::factory()->create(['description' => 'ACETAMINOFEN TABLETAS']);
    Product::factory()->create(['description' => 'IBUPROFENO JARABE']);

    $component = Livewire::actingAs(actingAsAdmin())
        ->test('shopping::pages.product-index')
        ->set('filterDescription', 'ACETAMINOFEN');

    expect($component->instance()->products->pluck('description')->all())
        ->toBe(['ACETAMINOFEN TABLETAS']);
});

it('selecciona un producto y permite editarlo', function () {
    $product = Product::factory()->create();

    Livewire::actingAs(actingAsAdmin())
        ->test('shopping::pages.product-index')
        ->set('selected', [(string) $product->id])
        ->assertSet('selected', [(string) $product->id]);
});

it('elimina varios productos seleccionados a la vez', function () {
    $p1 = Product::factory()->create();
    $p2 = Product::factory()->create();

    Livewire::actingAs(actingAsAdmin())
        ->test('shopping::pages.product-index')
        ->set('selected', [(string) $p1->id, (string) $p2->id])
        ->call('deleteSelected');

    expect(Product::count())->toBe(0);
});

it('valida y crea un producto', function () {
    $component = Livewire::actingAs(actingAsAdmin())
        ->test(ProductFormModal::class)
        ->call('openCreate');

    $component->call('save')->assertHasErrors([
        'form.item', 'form.description', 'form.measure_unit',
        'form.generic_name', 'form.generic_code',   // ← ahora también obligatorios
    ]);

    $component->set('form.item', 'PRD-001')
        ->set('form.description', 'Producto de prueba')
        ->set('form.measure_unit', 'Caja')
        ->set('form.generic_name', 'Acetaminofén')
        ->set('form.generic_code', 'GEN-001')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('show', false);

    expect(Product::where('item', 'PRD-001')->exists())->toBeTrue();
});

it('bloquea el acceso directo a /products sin el permiso products-read', function () {
    $role = Role::factory()->create();
    $role->syncPermissions([]);

    $user = User::factory()->create(['role_id' => $role->id]);
    $user->assignRole($role);

    $this->actingAs($user)->get(route('products.index'))->assertForbidden();
});

it('activa y desactiva productos seleccionados', function () {
    $active = Product::factory()->create(['status' => ProductStatus::Active]);
    $inactive = Product::factory()->create(['status' => ProductStatus::Inactive]);

    Livewire::actingAs(actingAsAdmin())
        ->test('shopping::pages.product-index')
        ->set('selected', [(string) $active->id, (string) $inactive->id])
        ->call('toggleSelected');

    expect($active->fresh()->status)->toBe(ProductStatus::Inactive)
        ->and($inactive->fresh()->status)->toBe(ProductStatus::Active);
});

it('muestra en el listado los productos eliminados (soft delete)', function () {
    $active = Product::factory()->create();
    $deleted = Product::factory()->create();
    $deleted->delete();

    $component = Livewire::actingAs(actingAsAdmin())
        ->test('shopping::pages.product-index');

    $ids = $component->instance()->products->pluck('id')->all();

    expect($ids)->toContain($active->id)
        ->and($ids)->toContain($deleted->id);
});

it('restaura productos eliminados seleccionados', function () {
    $deleted = Product::factory()->create();
    $deleted->delete();

    Livewire::actingAs(actingAsAdmin())
        ->test('shopping::pages.product-index')
        ->set('selected', [(string) $deleted->id])
        ->call('restoreSelected');

    expect($deleted->fresh()->trashed())->toBeFalse();
});

it('ignora registros ya eliminados al restaurar los que no lo están', function () {
    $active = Product::factory()->create();

    Livewire::actingAs(actingAsAdmin())
        ->test('shopping::pages.product-index')
        ->set('selected', [(string) $active->id])
        ->call('restoreSelected');

    expect($active->fresh()->trashed())->toBeFalse(); // no debió fallar ni cambiar nada
});
