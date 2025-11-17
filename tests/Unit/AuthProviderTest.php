<?php

use Laravilt\Auth\AuthProvider;

it('can create an auth provider', function () {
    $provider = AuthProvider::make();

    expect($provider)->toBeInstanceOf(AuthProvider::class);
});

it('can set guard', function () {
    $provider = AuthProvider::make()->guard('admin');

    expect($provider->getGuard())->toBe('admin');
});

it('can set model', function () {
    $provider = AuthProvider::make()->model('App\Models\User');

    expect($provider->getModel())->toBe('App\Models\User');
});

it('can set login by field', function () {
    $provider = AuthProvider::make()->loginBy('email');

    expect($provider->getLoginBy())->toBe('email');
});

it('can set login methods', function () {
    $provider = AuthProvider::make()->loginMethods([
        'email' => true,
        'social' => ['google', 'github'],
    ]);

    expect($provider->getLoginMethods())->toBe([
        'email' => true,
        'social' => ['google', 'github'],
    ]);
});

it('can enable registration', function () {
    $provider = AuthProvider::make()->registration();

    expect($provider->hasRegistration())->toBeTrue();
});

it('can enable email verification', function () {
    $provider = AuthProvider::make()->emailVerification();

    expect($provider->hasEmailVerification())->toBeTrue();
});

it('can enable password reset', function () {
    $provider = AuthProvider::make()->passwordReset();

    expect($provider->hasPasswordReset())->toBeTrue();
});

it('can enable two factor', function () {
    $provider = AuthProvider::make()->twoFactor(['totp', 'sms']);

    expect($provider->hasTwoFactor())->toBeTrue();
    expect($provider->getTwoFactorMethods())->toBe(['totp', 'sms']);
});

it('can enable profile', function () {
    $provider = AuthProvider::make()->profile();

    expect($provider->hasProfile())->toBeTrue();
});

it('can enable sessions', function () {
    $provider = AuthProvider::make()->sessions();

    expect($provider->hasSessions())->toBeTrue();
});

it('can enable api tokens', function () {
    $provider = AuthProvider::make()->apiTokens();

    expect($provider->hasApiTokens())->toBeTrue();
});

it('can configure with method chaining', function () {
    $provider = AuthProvider::make()
        ->guard('web')
        ->model('App\Models\User')
        ->loginBy('email')
        ->registration()
        ->emailVerification()
        ->twoFactor(['totp'])
        ->profile();

    expect($provider->getGuard())->toBe('web');
    expect($provider->getModel())->toBe('App\Models\User');
    expect($provider->getLoginBy())->toBe('email');
    expect($provider->hasRegistration())->toBeTrue();
    expect($provider->hasEmailVerification())->toBeTrue();
    expect($provider->hasTwoFactor())->toBeTrue();
    expect($provider->hasProfile())->toBeTrue();
});
