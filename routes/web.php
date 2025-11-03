<?php

use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VehicleCategoryController;


Route::apiResource('vehicle-categories', VehicleCategoryController::class);

Route::apiResource('vehicles', VehicleController::class);
Route::post('vehicles/{id}/images', [VehicleController::class, 'uploadImages']);