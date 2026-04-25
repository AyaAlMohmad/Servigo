<?php
use App\Http\Controllers\API\AuthController;

Route::prefix('auth')->group(function () {
    Route::get('/main-services', [AuthController::class, 'mainServices']);
    Route::post('/register/user', [AuthController::class, 'registerUser']);
    Route::post('/register/provider', [AuthController::class, 'registerProvider']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});