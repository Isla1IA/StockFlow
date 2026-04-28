<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\InventoryMovementApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\SaleApiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SummaryApiController;

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

    Route::prefix('summary')->name('summary.')->group(function () {
        Route::get('/sales/today', [SummaryApiController::class, 'salesToday'])->name('sales.today');
        Route::get('/sales/month', [SummaryApiController::class, 'salesMonth'])->name('sales.month');
        Route::get('/products/low-stock', [SummaryApiController::class, 'lowStockProducts'])->name('products.low-stock');
        Route::get('/products/top-selling', [SummaryApiController::class, 'topSellingProducts'])->name('products.top-selling');
        Route::get('/revenue/monthly', [SummaryApiController::class, 'monthlyRevenue'])->name('revenue.monthly');
    });
});
