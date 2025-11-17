<?php

use Illuminate\Support\Facades\Route;
use Laravilt\Auth\Http\Controllers\AuthController;
use Laravilt\Auth\Http\Controllers\SocialAuthController;
use Laravilt\Auth\Http\Controllers\TwoFactorController;

Route::middleware(config('laravilt-auth.routes.middleware', ['web']))
    ->prefix(config('laravilt-auth.routes.prefix', 'auth'))
    ->name('laravilt.auth.')
    ->group(function () {
        // Login routes
        Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.post');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // Registration routes
        Route::get('register', [AuthController::class, 'showRegistrationForm'])->name('register');
        Route::post('register', [AuthController::class, 'register'])->name('register.post');

        // Password reset routes
        Route::get('forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
        Route::post('forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

        // Email verification routes
        Route::get('verify-email', [AuthController::class, 'showVerifyEmailForm'])->name('verification.notice');
        Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
        Route::post('verification-notification', [AuthController::class, 'resendVerificationEmail'])->name('verification.send');

        // OTP routes
        Route::post('otp/send', [AuthController::class, 'sendOTP'])->name('otp.send');
        Route::post('otp/verify', [AuthController::class, 'verifyOTP'])->name('otp.verify');

        // Passwordless routes
        Route::post('passwordless/send', [AuthController::class, 'sendMagicLink'])->name('passwordless.send');
        Route::get('passwordless/login', [AuthController::class, 'passwordlessLogin'])->name('passwordless.login');

        // Social authentication routes
        Route::get('social/{provider}', [SocialAuthController::class, 'redirect'])->name('social.redirect');
        Route::get('social/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

        // Two-factor authentication routes
        Route::get('two-factor', [TwoFactorController::class, 'show'])->name('2fa.show');
        Route::post('two-factor', [TwoFactorController::class, 'verify'])->name('2fa.verify');
        Route::post('two-factor/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
        Route::post('two-factor/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
        Route::post('two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('2fa.recovery-codes');

        // WebAuthn routes
        Route::post('webauthn/register/options', [AuthController::class, 'webAuthnRegisterOptions'])->name('webauthn.register.options');
        Route::post('webauthn/register', [AuthController::class, 'webAuthnRegister'])->name('webauthn.register');
        Route::post('webauthn/login/options', [AuthController::class, 'webAuthnLoginOptions'])->name('webauthn.login.options');
        Route::post('webauthn/login', [AuthController::class, 'webAuthnLogin'])->name('webauthn.login');
    });
