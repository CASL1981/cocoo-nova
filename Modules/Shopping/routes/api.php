<?php

use Illuminate\Support\Facades\Route;
use Modules\Shopping\Http\Controllers\ShoppingController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('shoppings', ShoppingController::class)->names('shopping');
});
