<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OTPController;
use App\Farmer\Presentation\Controllers\FarmerController;
use App\Farm\Presentation\Controllers\HarvestController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::post('/otp/generate', [OTPController::class, 'generate']);
Route::post('/otp/verify', [OTPController::class, 'verify']);

/*
| Module 2 — Activity documentation & stock tracking (Developer 1)
| Farmer profile, Harvest registration, Stock tracking
*/
Route::middleware('auth:sanctum')->group(function () {
    // Farmer profile endpoints (admin only for registration, farmer/admin for others)
    Route::post('/farmers', [FarmerController::class, 'register'])->middleware('role:admin');
    Route::get('/farmers/me', [FarmerController::class, 'me'])->middleware('role:farmer,admin');
    Route::get('/farmers/{id}', [FarmerController::class, 'show'])->middleware('role:farmer,admin');
    Route::put('/farmers/{id}', [FarmerController::class, 'update'])->middleware('role:farmer,admin');

    // Harvest endpoints (farmer only)
    Route::get('/harvests', [HarvestController::class, 'index'])->middleware('role:farmer,admin');
    Route::post('/harvests', [HarvestController::class, 'record'])->middleware('role:farmer');
    Route::get('/harvests/{id}', [HarvestController::class, 'show'])->middleware('role:farmer,admin');
    Route::post('/harvests/{id}/send-to-warehouse', [HarvestController::class, 'sendToWarehouse'])->middleware('role:farmer');
});
