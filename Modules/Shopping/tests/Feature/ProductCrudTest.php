<?php

use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;
use Modules\Shopping\App\Livewire\Products\ProductFormModal;
use Modules\Shopping\App\Models\Product;

it('filtra productos por descripción', function () {
    Product::factory()->create(['description' => 'ACETAMINOFEN TABLETAS']);
    Product::factory()->create(['description' => 'IBUPROFENO JARABE']);

    $component = Livewire::actingAs(actingAsAdmin())
        ->test('shopping::product-index')
        ->set('filterName', 'ACETAMINOFEN');

    expect($component->instance()->products->pluck('description')->all())
        ->toBe(['ACETAMINOFEN TABLETAS']);
});

it('valida y crea un producto', function () {
    $component = Livewire::actingAs(actingAsAdmin())
        ->test(ProductFormModal::class)
        ->call('openCreate');

    $component->call('save')->assertHasErrors(['form.item', 'form.description', 'form.measure_unit']);

    $component->set('form.item', 'PRD-001')
        ->set('form.description', 'Producto de prueba')
        ->set('form.measure_unit', 'Caja')
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
