<?php

use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\V1\AdController;
use App\Http\Controllers\API\V1\Chat\OrderChatController;
use App\Http\Controllers\API\V1\Customer\Auth\CustomerAuthController;
use App\Http\Controllers\API\V1\Customer\Cart\CartController;
use App\Http\Controllers\API\V1\Customer\Category\CategoryController;
use App\Http\Controllers\API\V1\Customer\Chat\ChatController;
use App\Http\Controllers\API\V1\Customer\Checkout\CheckoutController;
use App\Http\Controllers\API\V1\Customer\Delivery\DeliveryTypeController;
use App\Http\Controllers\API\V1\Customer\Favorite\FavoriteController;
use App\Http\Controllers\API\V1\Customer\Item\ItemController;
use App\Http\Controllers\API\V1\Customer\Location\LocationController;
use App\Http\Controllers\API\V1\Customer\NotificationController;
use App\Http\Controllers\API\V1\Customer\Order\OrderController;
use App\Http\Controllers\API\V1\Customer\Profile\CardController;
use App\Http\Controllers\API\V1\Customer\Profile\CustomerController;
use App\Http\Controllers\API\V1\Customer\Review\ReviewController;
use App\Http\Controllers\API\V1\Customer\Ride\RideController;
use App\Http\Controllers\API\V1\Customer\Transport\TransportTypeController;
use App\Http\Controllers\API\V1\Customer\VendorController;
use App\Http\Controllers\API\V1\Customer\Voucher\VoucherController;
use App\Http\Controllers\API\V1\Customer\Tip\TipController;
use App\Http\Controllers\API\V1\Customer\Wallet\WalletController;
use Illuminate\Support\Facades\Route;



Route::group(['prefix' => 'customer'], function () {
    Route::post('register', [CustomerAuthController::class, 'register']);
    Route::post('login', [CustomerAuthController::class, 'login']);

    // Resend Otp
    Route::post('resend-otp', [CustomerAuthController::class, 'resendOtp']);

    // Forgot Password
    Route::post('forgot-password', [CustomerAuthController::class, 'forgotPassword']);
    Route::post('verify-otp-password', [CustomerAuthController::class, 'varifyOtpWithOutAuth']);
    Route::post('set-password', [CustomerAuthController::class, 'setPassword']);

    // Ads Route
    Route::get('ads', [AdController::class, 'index']);
    Route::get('ads/{id}', [AdController::class, 'show']);

    Route::middleware(['jwt.verify'])->group(function () {
        Route::delete('/delete/user', [CustomerController::class, 'deleteAccount']);
        Route::get('me', [CustomerController::class, 'me']);
        Route::post('profile/update', [CustomerController::class, 'updateProfile']);
        Route::post('password/change', [CustomerController::class, 'changePassword']);
        Route::post('notification-settings', [CustomerController::class, 'updateNotificationSettings']);
        Route::post('current-location', [LocationController::class, 'updateCurrentLocation']);

        Route::prefix('profile')->group(function () {
            Route::post('update-avatar', [UserController::class, 'updateAvatar']);
            Route::post('update-cover-photo', [UserController::class, 'updateCoverPhoto']);
            Route::get('cards/setup-intent', [CardController::class, 'generateSetupIntent']);
            Route::get('cards', [CardController::class, 'listCards']);
            Route::post('cards', [CardController::class, 'addCard']);
            Route::post('cards/default', [CardController::class, 'setDefaultCard']);
            Route::delete('cards/{id}', [CardController::class, 'deleteCard']);
        });

        Route::get('items', [ItemController::class, 'index']);
        Route::get('nearby-items', [ItemController::class, 'nearbyItems']);
        Route::get('fixed-categories', [CategoryController::class, 'fixedCategories']);


        Route::get('vendor/{id}/items', [VendorController::class, 'vendorItems']);
        Route::get('popular-vendors', [VendorController::class, 'popularVendors']);

        // voucher
        Route::get('vouchers', [VoucherController::class, 'index']);
        Route::get('vouchers/{id}', [VoucherController::class, 'show']);
        Route::post('vouchers/apply', [VoucherController::class, 'apply']);

        Route::get('cart', [CartController::class, 'getCart']);
        Route::post('cart/add', [CartController::class, 'addToCart']);
        Route::post('cart/update', [CartController::class, 'updateCartItem']);
        Route::delete('cart/remove', [CartController::class, 'removeFromCart']);
        Route::delete('cart/clear', [CartController::class, 'clearCart']);


        Route::get('delivery_type', [DeliveryTypeController::class, 'index']);

        Route::get('transport_type', [TransportTypeController::class, 'index']);
        Route::get('transport_type/categories', [TransportTypeController::class, 'getTransportTypeCategories']);

        Route::get('checkout/settings', [CheckoutController::class, 'getCheckoutSettings']);
        Route::post('checkout', [CheckoutController::class, 'checkout']);
        Route::post('checkout/repay', [CheckoutController::class, 'reinitiatePayment']);

        // Ride Chat
        Route::post('/rides/messages', [ChatController::class, 'sendRideMessage']);
        Route::get('/rides/messages', [ChatController::class, 'getRideMessages']);
        Route::post('/rides/messages/read', [ChatController::class, 'markRideMessageAsRead']);
        Route::get('/rides/participants', [ChatController::class, 'getRideParticipants']);

        // ride share
        Route::prefix('rides')->group(function () {
            Route::get('estimate', [RideController::class, 'estimate']);
            Route::post('request', [RideController::class, 'requestRide']);
            Route::get('history', [RideController::class, 'history']);
            Route::post('cancel', [RideController::class, 'cancelRide']);
            Route::post('review', [RideController::class, 'reviewRider']);
            Route::get('rider/{id}/profile', [RideController::class, 'riderProfile']);
            Route::get('{id}/location', [RideController::class, 'getLocation']);
            Route::get('{id}', [RideController::class, 'show']);
        });


        Route::post('location', [LocationController::class, 'setLocation']);
        Route::get('location', [LocationController::class, 'getLocationList']);
        Route::delete('location/{id}', [LocationController::class, 'deleteLocation']);

        // favorites
        Route::post('favorite/toggle', [FavoriteController::class, 'toggle']);
        Route::get('favorites', [FavoriteController::class, 'index']);


        // Support Chat
        Route::post('/support/messages', [ChatController::class, 'sendSupportMessage']);
        Route::get('/support/messages', [ChatController::class, 'getSupportMessages']);

        // Order Chat
        Route::post('/orders/messages', [ChatController::class, 'sendOrderMessage']);
        Route::get('/orders/messages', [ChatController::class, 'getOrderMessages']);
        Route::post('/orders/messages/read', [ChatController::class, 'markOrderMessageAsRead']);

        // Participants (optional)
        Route::get('/orders/participants', [ChatController::class, 'getOrderParticipants']);



        // order review
        Route::post('order/review', [ReviewController::class, 'store']);

        // order history
        Route::get('order/history', [OrderController::class, 'history']);
        Route::get('order/{id}', [OrderController::class, 'show']);
        Route::get('order/{id}/location', [OrderController::class, 'getLocation']);
        Route::post('order/cancel', [OrderController::class, 'cancelOrder']);

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('read-all', [NotificationController::class, 'markAllAsRead']);
            Route::delete('{id}', [NotificationController::class, 'destroy']);
        });

        // Wallet Routes
        Route::get('wallet', [WalletController::class, 'getWallet']);
        Route::get('transaction-history', [WalletController::class, 'history']);
        Route::post('wallet/add-money', [WalletController::class, 'addMoney']);

        // Tipping Routes
        Route::post('order/tip', [TipController::class, 'tipOrder']);
        Route::post('ride/tip', [TipController::class, 'tipRide']);

        Route::post('logout', [CustomerAuthController::class, 'logout']);
        Route::post('refresh', [CustomerAuthController::class, 'refresh']);
    });
});
