<?php

use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\V1\Chat\OrderChatController;
use App\Http\Controllers\API\V1\Customer\Chat\ChatController;
use App\Http\Controllers\API\V1\DeliveryMan\Auth\DeliveryManAuthController;
use App\Http\Controllers\API\V1\DeliveryMan\DeliveryManController;
use App\Http\Controllers\API\V1\DeliveryMan\NotificationController;
use App\Http\Controllers\API\V1\DeliveryMan\OrderController;
use App\Http\Controllers\API\V1\DeliveryMan\WalletController;
use App\Http\Controllers\API\V1\DeliveryMan\ReviewController;
use App\Http\Controllers\API\V1\DeliveryMan\DashboardController;
use Illuminate\Support\Facades\Route;



Route::group(['prefix' => 'delivery_man'], function () {
    Route::post('register', [DeliveryManAuthController::class, 'register']);
    Route::post('login', [DeliveryManAuthController::class, 'login']);

    // Resend Otp
    Route::post('resend-otp', [DeliveryManAuthController::class, 'resendOtp']);

    // Forgot Password
    Route::post('forgot-password', [DeliveryManAuthController::class, 'forgotPassword']);
    Route::post('verify-otp-password', [DeliveryManAuthController::class, 'varifyOtpWithOutAuth']);
    Route::post('set-password', [DeliveryManAuthController::class, 'setPassword']);

    Route::middleware(['jwt.verify'])->group(function () {
        Route::delete('/delete/user', [DeliveryManController::class, 'deleteAccount']);
        Route::get('me', [DeliveryManController::class, 'me']);
        Route::post('profile/update', [DeliveryManController::class, 'updateProfile']);
        Route::post('profile/update-avatar', [UserController::class, 'updateAvatar']);
        Route::post('profile/update-cover-photo', [UserController::class, 'updateCoverPhoto']);
        Route::post('vehicle/update', [DeliveryManController::class, 'updateVehicle']);
        Route::post('driving/update', [DeliveryManController::class, 'updateDriving']);
        Route::post('password/change', [DeliveryManController::class, 'changePassword']);
        Route::post('notification-settings', [DeliveryManController::class, 'updateNotificationSettings']);
        Route::get('reviews', [ReviewController::class, 'index']);
        Route::post('toggle-status', [DeliveryManController::class, 'toggleStatus']);
        Route::get('dashboard', [DashboardController::class, 'index']);


        // Order Chat
        Route::post('/orders/messages', [ChatController::class, 'sendOrderMessage']);
        Route::get('/orders/messages', [ChatController::class, 'getOrderMessages']);
        Route::post('/orders/messages/read', [ChatController::class, 'markOrderMessageAsRead']);

        // Participants (optional)
        Route::get('/orders/participants', [ChatController::class, 'getOrderParticipants']);

        // order management
        Route::prefix('orders')->group(function () {
            Route::get('available', [OrderController::class, 'availableOrders']);
            Route::get('current-request', [OrderController::class, 'currentRequest']);
            Route::post('{id}/status', [OrderController::class, 'updateStatus']);
            Route::post('location', [OrderController::class, 'updateLocation']);
            Route::get('location', [OrderController::class, 'getLocation']);
            Route::get('history', [OrderController::class, 'history']);
            Route::get('{id}', [OrderController::class, 'show']);

            // order confirm
            Route::post('/send-confirmation-code/{id}',[OrderController::class,'sendConfirmationCode']);
            Route::post('/verify-confirmation-code',[OrderController::class,'verifyConfirmationCode']);
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

        Route::post('logout', [DeliveryManAuthController::class, 'logout']);
        Route::post('refresh', [DeliveryManAuthController::class, 'refresh']);
    });
});
