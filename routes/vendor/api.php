<?php

use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\V1\Chat\OrderChatController;
use App\Http\Controllers\API\V1\Customer\Chat\ChatController;
use App\Http\Controllers\API\V1\Vendor\Auth\VendorAuthController;
use App\Http\Controllers\API\V1\Vendor\BusinessController;
use App\Http\Controllers\API\V1\Vendor\CardController;
use App\Http\Controllers\API\V1\Vendor\CategoryController;
use App\Http\Controllers\API\V1\Vendor\DashboardController;
use App\Http\Controllers\API\V1\Vendor\ItemController;
use App\Http\Controllers\API\V1\Vendor\NotificationController;
use App\Http\Controllers\API\V1\Vendor\OrderController;
use App\Http\Controllers\API\V1\Vendor\ReviewController;
use App\Http\Controllers\API\V1\Vendor\SupportTicketController;
use App\Http\Controllers\API\V1\Vendor\VendorController;
use App\Http\Controllers\API\V1\Vendor\VoucherController;
use App\Http\Controllers\API\V1\Vendor\WalletController;
use Illuminate\Support\Facades\Route;





Route::group(['prefix' => 'vendor'], function () {
    Route::post('register', [VendorAuthController::class, 'register']);
    Route::post('login', [VendorAuthController::class, 'login']);

    // Resend Otp
    Route::post('resend-otp', [VendorAuthController::class, 'resendOtp']);

    // Forgot Password
    Route::post('forgot-password', [VendorAuthController::class, 'forgotPassword']);
    Route::post('verify-otp-password', [VendorAuthController::class, 'varifyOtpWithOutAuth']);
    Route::post('set-password', [VendorAuthController::class, 'setPassword']);

    Route::middleware(['jwt.verify'])->group(function () {
        Route::delete('/delete/user', [VendorController::class, 'deleteAccount']);
        Route::get('me', [VendorController::class, 'me']);
        Route::post('profile/update', [VendorController::class, 'updateProfile']);
        Route::post('profile/update-avatar', [UserController::class, 'updateAvatar']);
        Route::post('profile/update-cover-photo', [UserController::class, 'updateCoverPhoto']);
        Route::post('password/change', [VendorController::class, 'changePassword']);
        Route::post('notification-settings', [VendorController::class, 'updateNotificationSettings']);
        
        Route::post('logout', [VendorAuthController::class, 'logout']);
        Route::post('refresh', [VendorAuthController::class, 'refresh']);

        // Card Management
        Route::get('cards/setup-intent', [CardController::class, 'generateSetupIntent']);
        Route::get('cards', [CardController::class, 'listCards']);
        Route::post('cards', [CardController::class, 'addCard']);
        Route::post('cards/default', [CardController::class, 'setDefaultCard']);
        Route::delete('cards/{id}', [CardController::class, 'deleteCard']);

        // Business Routes
        Route::post('business/setup', [BusinessController::class, 'setupBusiness']);
        Route::post('business/update', [BusinessController::class, 'updateBusiness']);

        // Category Routes
        Route::get('categories', [CategoryController::class, 'index']);
        Route::post('category/create', [CategoryController::class, 'store']);
        Route::post('category/update/{slug}', [CategoryController::class, 'update']);
        Route::delete('category/delete/{slug}', [CategoryController::class, 'destroy']);

        // Item Routes
        Route::get('items', [ItemController::class, 'index']);
        Route::post('item/create', [ItemController::class, 'store']);
        Route::get('item/show/{slug}', [ItemController::class, 'show']);
        Route::post('item/update/{slug}', [ItemController::class, 'update']);
        Route::delete('item/delete/{slug}', [ItemController::class, 'destroy']);

        // Dashboard Routes
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('dashboard/analytics', [DashboardController::class, 'analyticData']);
        Route::get('dashboard/recent-orders', [DashboardController::class, 'recentOrders']);

        // Order Chat
        Route::get('/delivery_man/conversations', [ChatController::class, 'getVendorConversations']);
        Route::post('/orders/messages', [ChatController::class, 'sendOrderMessage']);
        Route::get('/orders/messages', [ChatController::class, 'getOrderMessages']);
        Route::post('/orders/messages/read', [ChatController::class, 'markOrderMessageAsRead']);

        // Participants (optional)
        Route::get('/orders/participants', [ChatController::class, 'getOrderParticipants']);

        // Order Routes
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('card-orders', [OrderController::class, 'cardOrders']);
        Route::get('pickup-orders', [OrderController::class, 'pickupOrders']);
        Route::get('accepted-orders-ids', [OrderController::class, 'acceptedOrdersIds']);
        Route::post('orders/{id}/status', [OrderController::class, 'updateStatus']);

        // Review Routes
        Route::get('reviews', [ReviewController::class, 'index']);

        // Support Routes
        Route::post('support/contact', [SupportTicketController::class, 'store']);

        // Voucher Routes
        Route::get('vouchers', [VoucherController::class, 'index']);
        Route::post('vouchers/create', [VoucherController::class, 'store']);
        Route::delete('vouchers/delete/{id}', [VoucherController::class, 'destroy']);

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

    });
});
