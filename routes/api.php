<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\CustomerApiController;
use App\Http\Controllers\Api\ProductApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthTokenController::class, 'login'])->name('api.auth.login');
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthTokenController::class, 'logout'])->name('api.auth.logout');
    Route::get('/auth/me', [AuthTokenController::class, 'me'])->name('api.auth.me');

    Route::apiResource('products', ProductApiController::class);
    Route::apiResource('customers', CustomerApiController::class);
});
