<?php

use Illuminate\Support\Facades\Route;
use Laravilt\Auth\Http\Controllers\Auth\LoginController;
use Laravilt\Auth\Http\Controllers\Auth\OTPController;
use Laravilt\Auth\Http\Controllers\Auth\PasswordResetController;
use Laravilt\Auth\Http\Controllers\Auth\RegisterController;
use Laravilt\Auth\Http\Controllers\Auth\SocialAuthController;
use Laravilt\Auth\Http\Controllers\Auth\TwoFactorController;
use Laravilt\Auth\Http\Controllers\Auth\VerifyEmailController;
use Laravilt\Auth\Http\Controllers\Auth\WebAuthnController;

Route::middleware(config('laravilt-auth.routes.middleware', ['web']))
    ->prefix(config('laravilt-auth.routes.prefix', 'auth'))
    ->name('laravilt.auth.')
    ->group(function () {
        // Login routes
        Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('login', [LoginController::class, 'login'])->name('login.post');
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');

        // Registration routes
        Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
        Route::post('register', [RegisterController::class, 'register'])->name('register.post');

        // Password reset routes
        Route::get('forgot-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

        // Email verification routes
        Route::get('verify-email', [VerifyEmailController::class, 'show'])->name('verification.notice');
        Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, 'verify'])->name('verification.verify');
        Route::post('verification-notification', [VerifyEmailController::class, 'resend'])->name('verification.send');

        // OTP routes
        Route::get('otp/login', [OTPController::class, 'showLoginForm'])->name('otp.login');
        Route::post('otp/send', [OTPController::class, 'send'])->name('otp.send');
        Route::post('otp/verify', [OTPController::class, 'verify'])->name('otp.verify');
        Route::post('otp/resend', [OTPController::class, 'resend'])->name('otp.resend');

        // Social authentication routes
        Route::get('social/{provider}', [SocialAuthController::class, 'redirect'])->name('social.redirect');
        Route::get('social/{provider}/callback', [SocialAuthController::class, 'callback'])->name('social.callback');

        // Two-factor authentication challenge (during login)
        Route::get('two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
        Route::post('two-factor', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
        Route::post('two-factor/send-code', [TwoFactorController::class, 'sendCode'])->name('two-factor.send-code');

        // WebAuthn authentication routes
        Route::get('webauthn/login', [WebAuthnController::class, 'showLoginForm'])->name('webauthn.login');
        Route::post('webauthn/login/options', [WebAuthnController::class, 'generateAuthenticationOptions'])->name('webauthn.login.options');
        Route::post('webauthn/login/verify', [WebAuthnController::class, 'authenticate'])->name('webauthn.login.verify');
    });
