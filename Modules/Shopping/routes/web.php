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
