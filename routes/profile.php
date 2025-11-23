<?php

use Illuminate\Support\Facades\Route;
use Laravilt\Auth\Http\Controllers\Auth\SocialAuthController;
use Laravilt\Auth\Http\Controllers\Auth\TwoFactorController as AuthTwoFactorController;
use Laravilt\Auth\Http\Controllers\Auth\WebAuthnController as AuthWebAuthnController;
use Laravilt\Auth\Http\Controllers\Profile\DeleteAccountController;
use Laravilt\Auth\Http\Controllers\Profile\PasswordController;
use Laravilt\Auth\Http\Controllers\Profile\ProfileController;
use Laravilt\Auth\Http\Controllers\Profile\SessionsController;
use Laravilt\Auth\Http\Controllers\Profile\TokensController;
use Laravilt\Auth\Http\Controllers\Profile\TwoFactorController;
//
//Route::middleware(['web', 'auth'])
//    ->prefix('profile')
//    ->name('laravilt.profile.')
//    ->group(function () {
//        // Profile management
//        Route::get('/', [ProfileController::class, 'show'])->name('show');
//        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
//        Route::put('/', [ProfileController::class, 'update'])->name('update');
//        Route::put('/privacy', [ProfileController::class, 'updatePrivacy'])->name('privacy.update');
//        Route::delete('/avatar', [ProfileController::class, 'removeAvatar'])->name('avatar.remove');
//
//        // Password management
//        Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
//        Route::put('/password', [PasswordController::class, 'update'])->name('password.update');
//        Route::get('/password/confirm', [PasswordController::class, 'showConfirm'])->name('password.confirm');
//        Route::post('/password/confirm', [PasswordController::class, 'confirm'])->name('password.confirm.post');
//
//        // Two-factor authentication management
//        Route::get('/two-factor', [TwoFactorController::class, 'index'])->name('two-factor.index');
//        Route::post('/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
//        Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('two-factor.confirm');
//        Route::delete('/two-factor', [TwoFactorController::class, 'disable'])->name('two-factor.disable');
//        Route::get('/two-factor/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes'])->name('two-factor.recovery-codes');
//        Route::post('/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('two-factor.recovery-codes.regenerate');
//
//        // WebAuthn credential management (for profile)
//        Route::post('/two-factor/webauthn/options', [TwoFactorController::class, 'webauthnRegisterOptions'])->name('two-factor.webauthn.options');
//        Route::post('/two-factor/webauthn/register', [TwoFactorController::class, 'webauthnRegister'])->name('two-factor.webauthn.register');
//
//        // WebAuthn registration (for new credentials)
//        Route::get('/webauthn/register', [AuthWebAuthnController::class, 'showRegistrationPage'])->name('webauthn.register');
//        Route::post('/webauthn/register/options', [AuthWebAuthnController::class, 'generateRegistrationOptions'])->name('webauthn.register.options');
//        Route::post('/webauthn/register', [AuthWebAuthnController::class, 'register'])->name('webauthn.register.post');
//        Route::delete('/webauthn/{credentialId}', [AuthWebAuthnController::class, 'remove'])->name('webauthn.remove');
//
//        // Social accounts management
//        Route::delete('/social/{provider}', [SocialAuthController::class, 'unlink'])->name('social.unlink');
//
//        // Session management
//        Route::get('/sessions', [SessionsController::class, 'index'])->name('sessions.index');
//        Route::get('/sessions/{sessionId}', [SessionsController::class, 'show'])->name('sessions.show');
//        Route::delete('/sessions/{sessionId}', [SessionsController::class, 'destroy'])->name('sessions.destroy');
//        Route::delete('/sessions', [SessionsController::class, 'destroyOthers'])->name('sessions.destroy-others');
//
//        // API token management
//        Route::get('/tokens', [TokensController::class, 'index'])->name('tokens.index');
//        Route::post('/tokens', [TokensController::class, 'store'])->name('tokens.store');
//        Route::get('/tokens/{token}', [TokensController::class, 'show'])->name('tokens.show');
//        Route::put('/tokens/{token}', [TokensController::class, 'update'])->name('tokens.update');
//        Route::delete('/tokens/{token}', [TokensController::class, 'destroy'])->name('tokens.destroy');
//        Route::delete('/tokens', [TokensController::class, 'destroyAll'])->name('tokens.destroy-all');
//
//        // Account deletion
//        Route::get('/delete', [DeleteAccountController::class, 'show'])->name('delete.show');
//        Route::delete('/delete', [DeleteAccountController::class, 'destroy'])->name('delete.destroy');
//        Route::post('/delete/schedule', [DeleteAccountController::class, 'schedule'])->name('delete.schedule');
//        Route::delete('/delete/cancel', [DeleteAccountController::class, 'cancelScheduled'])->name('delete.cancel');
//        Route::get('/delete/export', [DeleteAccountController::class, 'export'])->name('delete.export');
//    });
