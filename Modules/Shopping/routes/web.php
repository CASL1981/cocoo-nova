<?php

use Illuminate\Support\Facades\Route;
use Modules\Shopping\Http\Controllers\ShoppingController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('shoppings', ShoppingController::class)->names('shopping');
});

Route::middleware(['auth', 'ensure.active', 'force.password.change', 'module.permission'])
    ->prefix('products')->name('products.')->group(function () {
        Route::livewire('/', 'shopping::pages.product-index')->name('index');
    });

// Proveedores comerciales (línea commercial-providers): medicamentos y dispositivos médicos.
// El tercero y su formato FO-300-01 son transversales (app/); aquí se gestiona la vinculación de Compras.
Route::middleware(['auth', 'ensure.active', 'force.password.change', 'module.permission'])
    ->prefix('shopping/providers')->name('commercial-providers.')->group(function (): void {
        Route::livewire('/', 'shopping::pages.commercial-provider-index')->name('index');
        Route::livewire('/{linkage}', 'shopping::pages.commercial-provider-show')->name('show');
    });
