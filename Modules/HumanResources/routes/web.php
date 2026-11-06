<?php

use Illuminate\Support\Facades\Route;

// El nombre de ruta inicia con el slug de permisos (hr-characteristics) para que
// module.permission infiera el permiso requerido.
Route::middleware(['auth', 'ensure.active', 'force.password.change', 'module.permission'])
    ->prefix('human-resources/characteristics')->name('hr-characteristics.')->group(function (): void {
        Route::livewire('/', 'humanresources::pages.hr-characteristic-index')->name('index');
    });
