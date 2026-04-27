<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\InventoryMovementApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\SaleApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthTokenController::class, 'login'])->name('api.auth.login');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthTokenController::class, 'logout'])->name('api.auth.logout');
    Route::get('/auth/me', [AuthTokenController::class, 'me'])->name('api.auth.me');

    Route::apiResource('products', ProductApiController::class);
    Route::apiResource('customers', CustomerApiController::class);

    Route::apiResource('sales', SaleApiController::class)->only(['index', 'store', 'show']);
    Route::post('sales/{sale}/cancel', [SaleApiController::class, 'cancel'])->name('sales.cancel');

    Route::apiResource('inventory-movements', InventoryMovementApiController::class)->only(['index', 'show']);
});
