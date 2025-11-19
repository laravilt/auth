<?php

namespace Laravilt\Auth;

use Illuminate\Support\ServiceProvider;
use Laravilt\Auth\Console\Commands\GenerateAuthCommand;
use Laravilt\Auth\Console\Commands\InstallAuthCommand;
use Laravilt\Auth\Methods\EmailPasswordMethod;
use Laravilt\Auth\Methods\PhoneOTPMethod;
use Laravilt\Auth\Methods\PasswordlessMethod;
use Laravilt\Auth\Methods\SocialLoginMethod;
use Laravilt\Auth\Methods\TwoFactorMethod;
use Laravilt\Auth\Methods\WebAuthnMethod;

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
        $this->loadRoutesFrom(__DIR__ . '/../routes/auth.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/profile.php');

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
            $this->commands([
                InstallAuthCommand::class,
                GenerateAuthCommand::class,
            ]);
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

        $authManager->registerMethod('email', EmailPasswordMethod::class);
        $authManager->registerMethod('phone', PhoneOTPMethod::class);
        $authManager->registerMethod('social', SocialLoginMethod::class);
        $authManager->registerMethod('passwordless', PasswordlessMethod::class);
        $authManager->registerMethod('webauthn', WebAuthnMethod::class);
        $authManager->registerMethod('2fa', TwoFactorMethod::class);
    }
}
