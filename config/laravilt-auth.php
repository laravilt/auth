<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Guard
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication guard that will be used
    | by the auth package.
    |
    */

    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Authentication Methods
    |--------------------------------------------------------------------------
    |
    | Configure which authentication methods are enabled for your application.
    |
    */

    'methods' => [
        'email' => true,
        'phone' => false,
        'social' => false,
        'passwordless' => false,
        'webauthn' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Authentication Providers
    |--------------------------------------------------------------------------
    |
    | Configure which social authentication providers are enabled.
    |
    */

    'social' => [
        'providers' => ['google', 'github', 'facebook'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    |
    | Configure two-factor authentication settings.
    |
    */

    'two_factor' => [
        'enabled' => false,
        'methods' => ['totp', 'sms', 'email'],
        'issuer' => env('APP_NAME', 'Laravilt'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Settings
    |--------------------------------------------------------------------------
    |
    | Configure OTP (One-Time Password) settings.
    |
    */

    'otp' => [
        'length' => 6,
        'expiry' => 5, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific authentication features.
    |
    */

    'features' => [
        'registration' => true,
        'email_verification' => true,
        'password_reset' => true,
        'profile' => true,
        'sessions' => true,
        'api_tokens' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Configure routing for authentication pages.
    |
    */

    'routes' => [
        'prefix' => 'auth',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | Configure view settings.
    |
    */

    'views' => [
        'theme' => 'default', // default, dark
        'rtl' => false,
    ],
];
