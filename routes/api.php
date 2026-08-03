<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OTPController;
use App\Http\Controllers\Operations\FarmerController;
use App\Http\Controllers\Operations\HarvestController;
use App\Http\Controllers\Operations\StockController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::post('/otp/generate', [OTPController::class, 'generate']);
Route::post('/otp/verify', [OTPController::class, 'verify']);

/*
| Module 2 — Activity documentation & stock tracking (Developer 1)
*/
Route::middleware(['auth:sanctum', 'role:farmer,admin'])->prefix('operations')->group(function () {
    Route::get('/farmer/me', [FarmerController::class, 'me']);
    Route::put('/farmer/me', [FarmerController::class, 'update']);

    Route::get('/harvests', [HarvestController::class, 'index']);
    Route::post('/harvests', [HarvestController::class, 'store']);
    Route::get('/harvests/{id}', [HarvestController::class, 'show']);

    Route::get('/stocks', [StockController::class, 'index']);
});
