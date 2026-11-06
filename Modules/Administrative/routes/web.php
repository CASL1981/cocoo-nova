<?php

use Illuminate\Support\Facades\Route;

// Proveedores administrativos (línea administrative-providers): papelería, cafetería y otros.
// El tercero y su formato FO-300-01 son transversales (app/); aquí se gestiona la vinculación administrativa.
Route::middleware(['auth', 'ensure.active', 'force.password.change', 'module.permission'])
    ->prefix('administrative/providers')->name('administrative-providers.')->group(function (): void {
        Route::livewire('/', 'administrative::pages.administrative-provider-index')->name('index');
        Route::livewire('/{linkage}', 'administrative::pages.administrative-provider-show')->name('show');
    });
