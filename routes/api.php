<?php
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProviderController;
use App\Http\Controllers\API\SearchController;
use App\Http\Controllers\API\ChatController;
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



Route::prefix('chat')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/button-status/{targetUserId}', [ChatController::class, 'checkChatButtonVisibility']);
    Route::post('/start/{providerId}', [ChatController::class, 'startPrivateChat']);
    Route::get('/provider/list', [ChatController::class, 'providerChatList']);
    Route::get('/customer/list', [ChatController::class, 'customerChatList']);
    Route::get('/{chatId}/messages', [ChatController::class, 'getMessages']);
    Route::post('/{chatId}/send', [ChatController::class, 'sendMessage']);
});

// ==================== Search Routes ====================
Route::prefix('search')->middleware(['auth:sanctum'])->group(function () {
   
    Route::get('/sub-services/{main_service_id}', [SearchController::class, 'getSubServices']);
    
 
    Route::get('/top-providers/{main_service_id}', [SearchController::class, 'getTopProviders']);
    

    Route::get('/providers', [SearchController::class, 'searchProviders']);
});

use App\Http\Controllers\API\RatingController;

// ==================== Ratings Routes ====================
Route::prefix('ratings')->middleware(['auth:sanctum'])->group(function () {
    
   
    Route::post('/', [RatingController::class, 'store']);
    
    
    Route::put('/{id}', [RatingController::class, 'update']);
    
  
    Route::delete('/{id}', [RatingController::class, 'destroy']);
    
   
    Route::get('/{id}', [RatingController::class, 'show']);
    
 
   Route::get('/user/my_ratings', [RatingController::class, 'myRatings']);
    
 
    Route::get('/provider/{provider_id}', [RatingController::class, 'getProviderRatings']);
    
   
    Route::get('/provider/{provider_id}/average', [RatingController::class, 'getProviderAverage']);
});