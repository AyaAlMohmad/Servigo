<?php
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProviderController;

// use Illuminate\Routing\Route;

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
    Route::middleware('auth:sanctum')->post('/delete-account-request', [AuthController::class, 'deleteAccountRequest']);
});


Route::prefix('provider')->middleware('auth:sanctum')->group(function () {
    Route::get('/sub-services', [ProviderController::class, 'subServices']);
    Route::post('/complete-profile', [ProviderController::class, 'completeProfile']);
});

use App\Http\Controllers\API\ChatController;

Route::prefix('chat')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/button-status/{targetUserId}', [ChatController::class, 'checkChatButtonVisibility']);
    Route::post('/start/{providerId}', [ChatController::class, 'startPrivateChat']);
    Route::get('/provider/list', [ChatController::class, 'providerChatList']);
    Route::get('/customer/list', [ChatController::class, 'customerChatList']);
    Route::get('/{chatId}/messages', [ChatController::class, 'getMessages']);
    Route::post('/{chatId}/send', [ChatController::class, 'sendMessage']);
});