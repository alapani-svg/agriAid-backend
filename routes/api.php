<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OTPController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — local SPA auth (Bearer token after OTP)
|--------------------------------------------------------------------------
*/

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::post('/otp/generate', [OTPController::class, 'generate']);
Route::post('/otp/verify', [OTPController::class, 'verify']);
