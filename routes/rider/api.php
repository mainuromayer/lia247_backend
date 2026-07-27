<?php

use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\V1\Chat\OrderChatController;
use App\Http\Controllers\API\V1\Customer\Chat\ChatController;
use App\Http\Controllers\API\V1\Rider\Auth\RiderAuthController;
use App\Http\Controllers\API\V1\Rider\NotificationController;
use App\Http\Controllers\API\V1\Rider\Ride\RideController;
use App\Http\Controllers\API\V1\Rider\RiderController;
use App\Http\Controllers\API\V1\Rider\WalletController;
use App\Http\Controllers\API\V1\Rider\DashboardController;
use App\Http\Controllers\API\V1\Rider\ReviewController;
use Illuminate\Support\Facades\Route;



Route::group(['prefix' => 'rider'], function () {
    Route::post('register', [RiderAuthController::class, 'register']);
    Route::post('login', [RiderAuthController::class, 'login']);

    // Resend Otp
    Route::post('resend-otp', [RiderAuthController::class, 'resendOtp']);

    // Forgot Password
    Route::post('forgot-password', [RiderAuthController::class, 'forgotPassword']);
    Route::post('verify-otp-password', [RiderAuthController::class, 'varifyOtpWithOutAuth']);
    Route::post('set-password', [RiderAuthController::class, 'setPassword']);

    Route::middleware(['jwt.verify'])->group(function () {
        Route::delete('/delete/user', [RiderController::class, 'deleteAccount']);
        Route::get('me', [RiderController::class, 'me']);
        Route::post('profile/update', [RiderController::class, 'updateProfile']);
        Route::post('profile/update-avatar', [UserController::class, 'updateAvatar']);
        Route::post('profile/update-cover-photo', [UserController::class, 'updateCoverPhoto']);
        Route::post('vehicle/update', [RiderController::class, 'updateVehicle']);
        Route::post('driving/update', [RiderController::class, 'updateDriving']);
        Route::post('password/change', [RiderController::class, 'changePassword']);
        Route::post('notification-settings', [RiderController::class, 'updateNotificationSettings']);
        Route::post('toggle-status', [RiderController::class, 'toggleStatus']);
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('reviews', [ReviewController::class, 'index']);


        // Order Chat
        Route::post('/orders/messages', [ChatController::class, 'sendOrderMessage']);
        Route::get('/orders/messages', [ChatController::class, 'getOrderMessages']);
        Route::post('/orders/messages/read', [ChatController::class, 'markOrderMessageAsRead']);

        // Participants (optional)
        Route::get('/orders/participants', [ChatController::class, 'getOrderParticipants']);

        // Ride Chat
        Route::post('/rides/messages', [ChatController::class, 'sendRideMessage']);
        Route::get('/rides/messages', [ChatController::class, 'getRideMessages']);
        Route::post('/rides/messages/read', [ChatController::class, 'markRideMessageAsRead']);
        Route::get('/rides/participants', [ChatController::class, 'getRideParticipants']);

        // ride share
        Route::prefix('rides')->group(function () {
            Route::get('nearby', [RideController::class, 'nearbyRides']);
            Route::get('current-request', [RideController::class, 'currentRequest']);
            Route::post('{id}/status', [RideController::class, 'updateStatus']);
            Route::get('history', [RideController::class, 'history']);
            Route::post('location', [RideController::class, 'updateLocation']);
            Route::get('location', [RideController::class, 'getLocation']);
            Route::get('{id}', [RideController::class, 'show']);
        });

        // Wallet Routes
        Route::get('wallet', [WalletController::class, 'getWallet']);
        Route::post('connect-account', [WalletController::class, 'connectAccount']);
        Route::post('withdraw', [WalletController::class, 'withdraw']);
        Route::get('transaction-history', [WalletController::class, 'history']);
        
        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('read-all', [NotificationController::class, 'markAllAsRead']);
            Route::delete('{id}', [NotificationController::class, 'destroy']);
        });

        Route::post('logout', [RiderAuthController::class, 'logout']);
        Route::post('refresh', [RiderAuthController::class, 'refresh']);
    });
});
