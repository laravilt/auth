<?php

use Laravilt\Auth\AuthManager;
use Laravilt\Auth\AuthProvider;

it('can create auth providers', function () {
    $manager = app(AuthManager::class);

    $provider = $manager->make('test');

    expect($provider)->toBeInstanceOf(AuthProvider::class);
    expect($manager->provider('test'))->toBe($provider);
});

it('can register custom auth methods', function () {
    $manager = app(AuthManager::class);

    $manager->registerMethod('custom', 'CustomAuthMethod');

    expect($manager->methods())->toHaveKey('custom');
});

it('can generate auth provider with config', function () {
    $manager = app(AuthManager::class);

    $provider = $manager->generate('users', [
        'guard' => 'web',
        'model' => 'App\Models\User',
        'loginBy' => 'email',
        'methods' => ['email', 'social'],
    ]);

    expect($provider->getGuard())->toBe('web');
    expect($provider->getModel())->toBe('App\Models\User');
    expect($provider->getLoginBy())->toBe('email');
});
