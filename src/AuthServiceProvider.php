<?php

namespace Laravilt\Auth;

use Illuminate\Support\ServiceProvider;
use Laravilt\Auth\Methods\EmailPasswordAuth;
use Laravilt\Auth\Methods\PhoneOTPAuth;
use Laravilt\Auth\Methods\PasswordlessAuth;
use Laravilt\Auth\Methods\SocialAuth;
use Laravilt\Auth\Methods\TwoFactorAuth;
use Laravilt\Auth\Methods\WebAuthnAuth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravilt-auth.php',
            'laravilt-auth'
        );

        // Register AuthManager as singleton
        $this->app->singleton('laravilt.auth', function ($app) {
            return new AuthManager(
                $app['auth'],
                $app['request']
            );
        });

        // Alias for easier access
        $this->app->alias('laravilt.auth', AuthManager::class);
    }

    /**
     * Boot services.
     */
    public function boot(): void
    {
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'laravilt-auth');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'laravilt-auth');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__ . '/../config/laravilt-auth.php' => config_path('laravilt-auth.php'),
            ], 'laravilt-auth-config');

            // Publish assets
            $this->publishes([
                __DIR__ . '/../dist' => public_path('vendor/laravilt/auth'),
            ], 'laravilt-auth-assets');

            // Publish views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/laravilt-auth'),
            ], 'laravilt-auth-views');

            // Publish migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'laravilt-auth-migrations');

            // Register commands
            // $this->commands([
            //     MakeAuth::class,
            // ]);
        }

        // Register default auth methods
        $this->registerAuthMethods();
    }

    /**
     * Register default authentication methods.
     */
    protected function registerAuthMethods(): void
    {
        $authManager = $this->app->make('laravilt.auth');

        $authManager->registerMethod('email', EmailPasswordAuth::class);
        $authManager->registerMethod('phone', PhoneOTPAuth::class);
        $authManager->registerMethod('social', SocialAuth::class);
        $authManager->registerMethod('passwordless', PasswordlessAuth::class);
        $authManager->registerMethod('webauthn', WebAuthnAuth::class);
        $authManager->registerMethod('2fa', TwoFactorAuth::class);
    }
}
