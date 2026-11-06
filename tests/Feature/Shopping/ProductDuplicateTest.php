<?php

use Livewire\Livewire;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Models\Product;

it('duplica el producto seleccionado con ítem COPIA e inactivo', function (): void {
    $source = Product::factory()->create(['item' => 'A100', 'status' => ProductStatus::Active])->fresh();

    Livewire::actingAs(userWith(['products-read', 'products-create']))
        ->test('shopping::pages.product-index')
        ->set('selected', [(string) $source->id])
        ->assertSeeHtml('wire:click="duplicateSelected"')
        ->call('duplicateSelected')
        ->assertDispatched('toast');

    $copy = Product::query()->where('item', 'A100-COPIA')->sole();
    expect($copy->description)->toBe($source->description)
        ->and($copy->generic_code)->toBe($source->generic_code)
        ->and($copy->status)->toBe(ProductStatus::Inactive);
});

it('no duplica productos eliminados', function (): void {
    $source = Product::factory()->create();
    $source->delete();

    Livewire::actingAs(actingAsAdmin())
        ->test('shopping::pages.product-index')
        ->set('selected', [(string) $source->id])
        ->call('duplicateSelected')
        ->assertDispatched('toast', type: 'error');

    expect(Product::withTrashed()->count())->toBe(1);
});
