<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehicleCategoryController;
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);


Route::apiResource('vehicle-categories', VehicleCategoryController::class);

Route::apiResource('vehicles', VehicleController::class);
Route::post('vehicles/{id}/images', [VehicleController::class, 'uploadImages']);