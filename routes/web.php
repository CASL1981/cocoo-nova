<?php

use App\Http\Controllers\GuideImageController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// routes/web.php
Route::middleware(['auth', 'ensure.active'])->group(function () {
    // Fuera de 'force.password.change' a propósito: es la única pantalla
    // a la que un usuario con clave temporal SÍ puede llegar.
    Route::livewire('/password/force-change', 'pages::force-password-change')
        ->name('password.force-change');

    Route::middleware('force.password.change')->group(function () {

        Route::view('dashboard', 'dashboard')->name('dashboard');

        // Guía de usuario: abierta a todo usuario autenticado; cada tema filtra por sus permisos (GuideCatalog).
        Route::livewire('/guide', 'pages::guide')->name('guide.index');
        Route::get('/guide/images/{path}', GuideImageController::class)
            ->where('path', '[a-z0-9-]+/[a-z0-9-]+\.png')
            ->name('guide.image');

        Route::middleware('module.permission')->prefix('users')->name('users.')->group(function () {
            Route::livewire('/', 'pages::user-index')->name('index');
        });

        Route::middleware('module.permission')->prefix('roles')->name('roles.')->group(function () {
            Route::livewire('/', 'pages::role-index')->name('index');
        });

        Route::middleware('module.permission')->prefix('service-points')->name('service-points.')->group(function () {
            Route::livewire('/', 'pages::service-point-index')->name('index');
        });

        Route::middleware('module.permission')->prefix('characteristics')->name('characteristics.')->group(function () {
            Route::livewire('/', 'pages::characteristic-index')->name('index');
        });

        Route::middleware('module.permission')->prefix('processes')->name('processes.')->group(function () {
            Route::livewire('/', 'pages::process-index')->name('index');
        });

        Route::middleware('module.permission')->prefix('process-substitutions')->name('process-substitutions.')->group(function () {
            Route::livewire('/', 'pages::process-substitution-index')->name('index');
        });

        Route::middleware('module.permission')->prefix('geographic-locations')->name('geographic-locations.')->group(function () {
            Route::livewire('/', 'pages::geographic-location-index')->name('index');
        });

        Route::middleware('module.permission')->prefix('company-settings')->name('company-settings.')->group(function () {
            Route::livewire('/', 'pages::company-settings')->name('index');
        });
    });
});

require __DIR__.'/settings.php';
