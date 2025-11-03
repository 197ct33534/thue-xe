<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehicleCategoryController;
use App\Http\Controllers\VehicleController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public routes
Route::get('/vehicles', [VehicleController::class, 'index']);
Route::get('/vehicles/{id}', [VehicleController::class, 'show']);
Route::get('/vehicle-categories', [VehicleCategoryController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    // User chung
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Customer routes
    Route::middleware('role:customer')->group(function () {
        // Có thể thêm: đặt xe, xem lịch sử thuê...
    });

    // Admin routes
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('vehicle-categories', VehicleCategoryController::class)->except(['index', 'show']);
        Route::apiResource('vehicles', VehicleController::class)->except(['index', 'show']);
        Route::post('vehicles/{id}/images', [VehicleController::class, 'uploadImages']);
    });
});