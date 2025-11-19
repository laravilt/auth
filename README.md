![Screenshot](https://raw.githubusercontent.com/laravilt/auth/master/arts/cover.jpg)

# Laravilt Auth

Complete authentication system for Laravilt with multiple authentication methods, multi-guard support, and comprehensive security features.

## Installation

```bash
composer require laravilt/auth
```

## Features

- ✅ Multiple authentication methods (Email/Password, Phone/OTP, Social, Passwordless, WebAuthn)
- ✅ Two-factor authentication (TOTP, SMS, Email, WebAuthn)
- ✅ Multi-guard support with custom guard implementation
- ✅ Social login (Google, GitHub, Facebook, Twitter, LinkedIn)
- ✅ Email verification & Password reset
- ✅ Profile, Session & API token management
- ✅ Multi-language (English/Arabic) with RTL support
- ✅ Console commands for installation and configuration
- ✅ Comprehensive notification system
- ✅ Security features (session management, login alerts, account deletion)

## Quick Start

### Install the package

```bash
composer require laravilt/auth
php artisan laravilt:auth:install
```

The installation wizard will guide you through:
- Selecting authentication methods
- Configuring 2FA options
- Choosing social providers
- Publishing assets and views

### Manual Configuration

```php
use Laravilt\Auth\AuthProvider;

$auth = AuthProvider::make()
    ->guard('web')
    ->model(User::class)
    ->loginBy('email')
    ->registration()
    ->emailVerification()
    ->passwordReset()
    ->twoFactor(['totp', 'sms'])
    ->withSocial(['google', 'github'])
    ->profile()
    ->sessions()
    ->apiTokens();
```

## Current Implementation Status

### ✅ Core Backend (100% Complete)

#### 1. Core Architecture
- ✅ **AuthServiceProvider** - Service provider with auto-discovery
- ✅ **AuthManager** - Central authentication manager
- ✅ **AuthProvider** - Fluent configuration builder
- ✅ **Facades/Auth** - Facade for easy access

#### 2. Authentication Methods (6/6)
- ✅ **EmailPasswordMethod** - Traditional email/password authentication
- ✅ **PhoneOTPMethod** - Phone number with OTP verification
- ✅ **SocialLoginMethod** - OAuth social authentication
- ✅ **PasswordlessMethod** - Magic link authentication
- ✅ **WebAuthnMethod** - Passkeys/security keys (FIDO2)
- ✅ **TwoFactorMethod** - Two-factor authentication layer

#### 3. Services (3/3)
- ✅ **OTPService** - OTP generation and verification
- ✅ **TwoFactorService** - 2FA management (TOTP, SMS, Email)
- ✅ **WebAuthnService** - WebAuthn credential management

#### 4. Two-Factor Providers (3/3)
- ✅ **TotpProvider** - Time-based OTP with Google2FA
- ✅ **SmsProvider** - SMS-based verification
- ✅ **EmailProvider** - Email-based verification

#### 5. Guards & Providers (3/3)
- ✅ **LaraviltGuard** - Custom guard with multi-method support
- ✅ **LaraviltAuthProvider** - Enhanced user provider
- ✅ **CustomAuthProvider** - Extended provider with OTP/magic token support

#### 6. Models (3/3)
- ✅ **PersonalAccessToken** - Sanctum API tokens
- ✅ **TwoFactorCode** - OTP code storage
- ✅ **SocialAccount** - Social login accounts

#### 7. Migrations (5/5)
- ✅ `add_two_factor_columns_to_users_table`
- ✅ `create_social_accounts_table`
- ✅ `create_webauthn_credentials_table`
- ✅ `create_two_factor_codes_table`
- ✅ `create_personal_access_tokens_table`

#### 8. Controllers (14/14)
**Auth Controllers (8/8):**
- ✅ **LoginController** - Multi-method login handling
- ✅ **RegisterController** - User registration
- ✅ **VerifyEmailController** - Email verification
- ✅ **PasswordResetController** - Password reset flow
- ✅ **SocialAuthController** - OAuth callbacks
- ✅ **OTPController** - OTP sending and verification
- ✅ **WebAuthnController** - WebAuthn registration/authentication
- ✅ **TwoFactorController** - 2FA setup and verification

**Profile Controllers (6/6):**
- ✅ **ProfileController** - Profile viewing and editing
- ✅ **PasswordController** - Password changes
- ✅ **TwoFactorController** - 2FA management
- ✅ **SessionsController** - Active session management
- ✅ **TokensController** - API token management
- ✅ **DeleteAccountController** - Account deletion with GDPR export

#### 9. Middleware (3/3)
- ✅ **Authenticate** - Authentication check
- ✅ **EnsureEmailVerified** - Email verification requirement
- ✅ **RequireTwoFactor** - 2FA verification requirement

#### 10. Notifications (4/4)
- ✅ **VerifyEmail** - Email verification notification
- ✅ **ResetPassword** - Password reset notification
- ✅ **TwoFactorCode** - 2FA code delivery (Email/SMS)
- ✅ **LoginNotification** - Login alerts for security

#### 11. Console Commands (2/2)
- ✅ **InstallAuthCommand** - Interactive installation wizard
- ✅ **GenerateAuthCommand** - Generate auth configuration

#### 12. Routes (2/2)
- ✅ **auth.php** - Authentication routes (login, register, verify, reset, 2FA, social, WebAuthn)
- ✅ **profile.php** - Profile routes (profile, password, 2FA, sessions, tokens, delete)

#### 13. Database Seeders (1/1)
- ✅ **AuthSeeder** - Test users and sample data

#### 14. Translations (2/2)
- ✅ **en/auth.php** - 100+ English translation strings
- ✅ **ar/auth.php** - 100+ Arabic translation strings with RTL support

#### 15. Testing (20 tests passing)
- ✅ **AuthManagerTest** - 3 tests
- ✅ **AuthProviderTest** - 13 tests
- ✅ **OTPServiceTest** - 4 tests
- ✅ **PHPStan Level 5** - 0 errors (with baseline)

### 📋 Pending Frontend Components

#### Views (14 Blade files needed)
**Auth Views (7):**
- ⏳ login.blade.php
- ⏳ register.blade.php
- ⏳ verify-email.blade.php
- ⏳ reset-password.blade.php
- ⏳ forgot-password.blade.php
- ⏳ two-factor.blade.php
- ⏳ social/callback.blade.php

**Profile Views (7):**
- ⏳ show.blade.php
- ⏳ edit.blade.php
- ⏳ password.blade.php
- ⏳ two-factor.blade.php
- ⏳ sessions.blade.php
- ⏳ tokens.blade.php
- ⏳ delete-account.blade.php

#### JavaScript (7 files needed)
- ⏳ auth/login.js
- ⏳ auth/register.js
- ⏳ auth/two-factor.js
- ⏳ auth/webauthn.js
- ⏳ profile/edit.js
- ⏳ profile/sessions.js
- ⏳ profile/tokens.js

#### CSS (1 file needed)
- ⏳ auth.css

#### Feature Tests (13 test files needed)
- ⏳ Auth feature tests (8 files)
- ⏳ Profile feature tests (5 files)

## Usage

### Console Commands

```bash
# Interactive installation
php artisan laravilt:auth:install

# Generate custom auth configuration
php artisan laravilt:auth:generate admin-auth --guard=session --model=App\\Models\\Admin
```

### Authentication Manager

```php
use Laravilt\Auth\Facades\Auth;

// Create auth provider
$auth = Auth::make('admin')
    ->guard('admin')
    ->model(Admin::class)
    ->loginMethods(['email', 'otp'])
    ->twoFactor(['totp', 'email']);

// Check authentication
if (Auth::check()) {
    $user = Auth::user();
}

// Use specific method
$authMethod = Auth::method('email');
if ($authMethod->canHandle($request)) {
    $user = $authMethod->authenticate($request);
}
```

### Two-Factor Authentication

```php
use Laravilt\Auth\Services\TwoFactorService;

$twoFactor = app(TwoFactorService::class);

// Enable 2FA with TOTP
$data = $twoFactor->enable($user, 'totp');
// Returns: ['secret', 'qr_code', 'recovery_codes']

// Verify code
$isValid = $twoFactor->verify($user, $code, 'totp');

// Disable 2FA
$twoFactor->disable($user);
```

### OTP Service

```php
use Laravilt\Auth\Services\OTPService;

$otp = app(OTPService::class);

// Send OTP
$otp->send('+1234567890');

// Verify OTP
if ($otp->verify('+1234567890', '123456')) {
    // Valid OTP
}
```

### WebAuthn

```php
use Laravilt\Auth\Services\WebAuthnService;

$webAuthn = app(WebAuthnService::class);

// Generate registration options
$options = $webAuthn->generateRegistrationOptions($user);

// Verify and store credential
$credential = $webAuthn->verifyAndStoreCredential($user, $publicKeyCredential);
```

### Social Authentication

```php
use Laravilt\Auth\Http\Controllers\Auth\SocialAuthController;

// In your controller
public function redirectToGoogle()
{
    return Socialite::driver('google')->redirect();
}

public function handleGoogleCallback()
{
    $user = Socialite::driver('google')->user();
    // User is authenticated
}
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=laravilt-auth-config
```

### Key Configuration Options

```php
return [
    'guards' => [
        'web' => ['driver' => 'session', 'provider' => 'users'],
        'api' => ['driver' => 'token', 'provider' => 'users'],
    ],

    'methods' => [
        'email' => true,
        'phone' => false,
        'social' => ['google', 'github'],
        'passwordless' => false,
        'webauthn' => false,
    ],

    'features' => [
        'registration' => true,
        'email_verification' => true,
        'password_reset' => true,
        'two_factor' => ['totp', 'email'],
        'profile' => true,
        'sessions' => true,
        'api_tokens' => true,
    ],

    'social' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => '/auth/social/google/callback',
        ],
    ],

    'two_factor' => [
        'default_method' => 'totp',
        'methods' => ['totp', 'sms', 'email'],
        'totp' => [
            'issuer' => env('APP_NAME'),
            'window' => 1,
        ],
    ],

    'otp' => [
        'length' => 6,
        'expiry' => 5, // minutes
    ],
];
```

## Publishing Assets

```bash
# Publish all assets
php artisan vendor:publish --provider="Laravilt\Auth\AuthServiceProvider"

# Or publish selectively
php artisan vendor:publish --tag=laravilt-auth-config
php artisan vendor:publish --tag=laravilt-auth-migrations
php artisan vendor:publish --tag=laravilt-auth-views
php artisan vendor:publish --tag=laravilt-auth-assets
```

## Testing

```bash
# Run tests
composer test

# Run with coverage
composer test-coverage

# Run static analysis
composer analyse

# Format code
composer format
```

### Current Test Results
```
Tests:    20 passed (31 assertions)
Duration: 0.26s
PHPStan:  Level 5 - 0 errors
```

## Security Features

- ✅ Multi-factor authentication (TOTP, SMS, Email, WebAuthn)
- ✅ Session management with device tracking
- ✅ Login notifications and alerts
- ✅ API token management with expiration
- ✅ Account deletion with GDPR-compliant data export
- ✅ Password confirmation for sensitive operations
- ✅ Rate limiting on authentication endpoints
- ✅ CSRF protection
- ✅ Secure password hashing

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Laravilt Team](https://github.com/laravilt)
- [All Contributors](../../contributors)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.
