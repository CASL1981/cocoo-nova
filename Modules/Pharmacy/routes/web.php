<?php

use Illuminate\Support\Facades\Route;
use Modules\Pharmacy\Http\Controllers\PharmacyController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pharmacies', PharmacyController::class)->names('pharmacy');
});

// El nombre de ruta inicia con el slug del módulo de permisos (stock-outs)
// para que el middleware module.permission infiera el permiso requerido.
Route::middleware(['auth', 'ensure.active', 'force.password.change', 'module.permission'])
    ->prefix('pharmacy/stock-outs')->name('stock-outs.')->group(function (): void {
        Route::livewire('/', 'pharmacy::pages.stock-out-index')->name('index');
    });

Route::middleware(['auth', 'ensure.active', 'force.password.change', 'module.permission'])
    ->prefix('pharmacy/pendings')->name('pendings.')->group(function (): void {
        Route::livewire('/', 'pharmacy::pages.pending-index')->name('index');
        // "show" se traduce a pendings-read en module.permission; los ítems se gestionan aquí.
        Route::livewire('/{pending}', 'pharmacy::pages.pending-show')->name('show');
    });

// Consulta de ítems: "items" no se mapea en module.permission; la página autoriza
// en mount() con PendingItemPolicy::viewAny (pendings-read).
Route::middleware(['auth', 'ensure.active', 'force.password.change', 'module.permission'])
    ->prefix('pharmacy/pending-items')->name('pendings.')->group(function (): void {
        Route::livewire('/', 'pharmacy::pages.pending-item-query')->name('items');
    });

// Productos nuevos (línea de permisos new-products): solicitudes de las farmacias y gestión de
// Oficina Principal. "management" no se mapea en module.permission: la página autoriza en mount()
// con NewProductPolicy::manage (new-products-read y usuario de Oficina Principal).
Route::middleware(['auth', 'ensure.active', 'force.password.change', 'module.permission'])
    ->prefix('pharmacy/new-products')->name('new-products.')->group(function (): void {
        Route::livewire('/', 'pharmacy::pages.new-product-index')->name('index');
        Route::livewire('/management', 'pharmacy::pages.new-product-management')->name('management');
    });

// Gestión de Oficina Principal (línea de permisos inventory-balances). "assignment" no se
// mapea en module.permission: la página autoriza en mount() con inventory-balances-read.
Route::middleware(['auth', 'ensure.active', 'force.password.change', 'module.permission'])
    ->prefix('pharmacy/management')->name('inventory-balances.')->group(function (): void {
        Route::livewire('/inventory', 'pharmacy::pages.inventory-balance-index')->name('index');
        Route::livewire('/stock-assignment', 'pharmacy::pages.stock-assignment-index')->name('assignment');
        // "transfer-documents" no se mapea en module.permission: autoriza en mount() (inventory-balances-read).
        Route::livewire('/transfer-documents', 'pharmacy::pages.transfer-document-index')->name('transfer-documents');
        // "consolidation" no se mapea en module.permission: autoriza en mount() (inventory-balances-read).
        Route::livewire('/consolidation', 'pharmacy::pages.pending-consolidation')->name('consolidation');
    });
