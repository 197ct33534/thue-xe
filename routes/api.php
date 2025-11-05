<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehicleCategoryController;
use App\Http\Controllers\VehicleController;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/vehicles', [VehicleController::class, 'index']);
    Route::get('/vehicles/{id}', [VehicleController::class, 'show']);
    Route::get('/vehicle-categories', [VehicleCategoryController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        // User chung
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Customer routes
        Route::middleware('role:customer')->group(function () {
            // Các route dành cho khách hàng
        });

        // Admin routes
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('vehicle-categories', VehicleCategoryController::class)->except(['index', 'show']);
            Route::apiResource('vehicles', VehicleController::class)->except(['index', 'show']);
            Route::post('vehicles/{id}/images', [VehicleController::class, 'uploadImages']);
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/bookings/my-bookings', [BookingController::class, 'myBookings']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);
        Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
        Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus']);
    });
});

Route::post('/bookings/payment/callback/{method}', [BookingController::class, 'callback'])
    ->name('booking.payment.callback');

Route::post('/bookings/payment/ipn/{method}', [BookingController::class, 'ipn'])
    ->name('booking.payment.ipn');