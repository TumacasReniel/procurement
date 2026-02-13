<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Auth\SocialAuthController;

Route::get('/auth/{provider}', [SocialAuthController::class, 'redirect'])
    ->where('provider', 'google|facebook');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', 'google|facebook');

Route::middleware('guest')->group(function () {
    Route::post('otp/send', [OtpController::class, 'send']);
    Route::post('otp/verify', [OtpController::class, 'verify']);

    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/activation', [ProfileController::class, 'activation'])->name('activation');
    Route::put('activate', [ProfileController::class, 'activate']);
    Route::post('activation-check', [ProfileController::class, 'check']);
    Route::post('photo', [ProfileController::class, 'store']);
    Route::get('/profile', [ProfileController::class, 'index']);
    Route::put('/profile/updated', [ProfileController::class, 'update']);
    Route::get('two-factor-challenge', [TwoFactorAuthenticationController::class, 'index'])->name('twofactor');
    Route::post('two-factor-challenge', [TwoFactorAuthenticationController::class, 'store']);
    Route::post('two-factor/enable', [TwoFactorAuthenticationController::class, 'enable']);
    Route::post('two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm']);
    Route::post('two-factor/disable', [TwoFactorAuthenticationController::class, 'disable']);
    Route::post('two-factor/fetch', [TwoFactorAuthenticationController::class, 'fetch']);
    Route::get('two-factor/recovery', [TwoFactorAuthenticationController::class, 'recovery']);
    Route::get('two-factor/regenerate', [TwoFactorAuthenticationController::class, 'regenerate']);
    Route::get('verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});