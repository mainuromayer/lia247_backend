<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\ForgotPasswordOtpController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Web\backend\DashboardController;
use Illuminate\Support\Facades\Route;



Route::middleware(['web'])->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('admin/login', [AuthenticatedSessionController::class, 'store'])->name('admin.login');

    // Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
    //     ->name('password.request');

    // Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
    //     ->name('password.email');

    // Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
    //     ->name('password.reset');

    // Route::post('reset-password', [NewPasswordController::class, 'store'])
    //     ->name('password.store');

    Route::get('forgot-password', function () {
        return view('auth.forgot-password');
    })->name('password.request.otp');

    Route::post('forgot-password/otp', [ForgotPasswordOtpController::class, 'sendOtp'])
        ->name('password.otp.send');

    Route::get('verify-otp', function () {
        return view('auth.verify-otp');
    })->name('password.otp.verify.page');

    Route::post('verify-otp', [ForgotPasswordOtpController::class, 'verifyOtp'])
        ->name('password.otp.verify');

    Route::get('reset-password', function () {
        if (!session('password_reset_allowed')) {
            return redirect()->route('password.request.otp')
                ->with('error', 'Please verify OTP first.');
        }
        return view('auth.reset-password');
    })->name('password.reset.otp');

    Route::post('reset-password', [ForgotPasswordOtpController::class, 'resetPassword'])
        ->name('password.update.otp');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('admin/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('admin.logout');



    Route::get('dashboard', [DashboardController::class, 'dashboard'])->middleware('admin')->name('admin.dashboard');
});
