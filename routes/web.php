<?php

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

        Route::middleware('module.permission')->prefix('users')->name('users.')->group(function () {
            Route::livewire('/', 'pages::user-index')->name('index');
        });

        Route::middleware('module.permission')->prefix('roles')->name('roles.')->group(function () {
            Route::livewire('/', 'pages::role-index')->name('index');
        });

        Route::middleware('module.permission')->prefix('service-points')->name('service-points.')->group(function () {
            Route::livewire('/', 'pages::service-point-index')->name('index');
        });
    });
});


require __DIR__.'/settings.php';
