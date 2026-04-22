<?php

use App\Http\Controllers\Api\AuthTokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthTokenController::class, 'login'])->name('api.auth.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthTokenController::class, 'logout'])->name('api.auth.logout');
        Route::get('/me', [AuthTokenController::class, 'me'])->name('api.auth.me');
    });
});
