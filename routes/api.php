<?php

use Illuminate\Support\Facades\Route;
use App\Identity\Presentation\Controllers\OTPController;
use App\Identity\Presentation\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('api')->group(function () {
    // Authentication routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

    // OTP routes
    Route::post('/otp/generate', [OTPController::class, 'generate']);
    Route::post('/otp/verify', [OTPController::class, 'verify']);
});
