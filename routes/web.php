<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\Webhook\StripeWebhookController;



Route::get('/', function () {
    // return view('frontend.welcome');
    return redirect()->route('login');
})->name('home');


require __DIR__.'/auth.php';

// Stripe Connect Vendor Routes
use App\Http\Controllers\API\V1\Vendor\WalletController as VendorWalletController;
Route::get('connect-account/success/{userID}', [VendorWalletController::class, 'successConnect'])->name('connect_account.success');
Route::get('connect-account/refresh/{userID}', [VendorWalletController::class, 'refresh'])->name('connect_account.refresh');

// Stripe Connect Delivery Man Routes
use App\Http\Controllers\API\V1\DeliveryMan\WalletController as DeliveryManWalletController;
Route::get('delivery-man/connect-account/success/{userID}', [DeliveryManWalletController::class, 'successConnect'])->name('connect_account.delivery_man.success');
Route::get('delivery-man/connect-account/refresh/{userID}', [DeliveryManWalletController::class, 'refresh'])->name('connect_account.delivery_man.refresh');

// Stripe Connect Rider Routes
use App\Http\Controllers\API\V1\Rider\WalletController as RiderWalletController;
Route::get('rider/connect-account/success/{userID}', [RiderWalletController::class, 'successConnect'])->name('connect_account.rider.success');
Route::get('rider/connect-account/refresh/{userID}', [RiderWalletController::class, 'refresh'])->name('connect_account.rider.refresh');

Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

// Mobile App User Account Management & Deletion Portal (Google Play & Apple App Store Compliance)
use App\Http\Controllers\Web\AccountDeletionController;

Route::prefix('delete-account')->group(function () {
    Route::get('/', [AccountDeletionController::class, 'index'])->name('account.delete.index');
    Route::post('/login', [AccountDeletionController::class, 'login'])->name('account.delete.login');
    Route::get('/portal', [AccountDeletionController::class, 'portal'])->name('account.delete.portal');
    Route::post('/confirm', [AccountDeletionController::class, 'confirmDelete'])->name('account.delete.confirm');
    Route::post('/logout', [AccountDeletionController::class, 'logout'])->name('account.delete.logout');
});

// Alias for store compliance URL pattern
Route::get('account/delete', function () {
    return redirect()->route('account.delete.index');
});
