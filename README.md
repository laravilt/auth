# Laravilt Auth Package

Complete authentication system for Laravilt with multiple authentication methods, multi-guard support, and panel integration.

## Installation

```bash
composer require laravilt/auth
```

## Features

- ✅ Multiple authentication methods (Email/Password, Phone/OTP, Social, Passwordless, WebAuthn)
- ✅ Two-factor authentication (TOTP, SMS, Email)
- ✅ Multi-guard support
- ✅ Social login (Google, GitHub, Facebook)
- ✅ Email verification & Password reset
- ✅ Profile, Session & API token management
- ✅ Multi-language (English/Arabic) with RTL support

## Current Implementation Status

### ✅ Core Components (Completed)
- AuthServiceProvider, AuthManager, AuthProvider
- 6 Authentication Methods (Email, Phone/OTP, Social, Passwordless, WebAuthn, 2FA)
- 3 Services (OTPService, TwoFactorService, WebAuthnService)
- 3 TwoFactor Providers (TOTP, SMS, Email)
- 3 Models (SocialAccount, TwoFactorCode, PersonalAccessToken)
- 4 Migrations (users 2FA columns, social accounts, webauthn credentials, 2FA codes)
- Complete routing configuration
- English & Arabic translations
- 20 unit tests passing, PHPStan Level 5 (0 errors)

### 📋 Pending Components
Controllers (14), Middleware (3), Guards (1), Providers (2), Notifications (4), Commands (2), Views (14 Blade + Vue), JavaScript (7 files), CSS (1 file), Feature Tests, Documentation

See [Implementation Details](#implementation-details) below for full breakdown.

## Usage

### Basic Setup

```php
use Laravilt\Auth\AuthProvider;

$auth = AuthProvider::make()
    ->guard('web')
    ->model(User::class)
    ->loginBy('email')
    ->registration()
    ->emailVerification()
    ->twoFactor(['totp'])
    ->withSocial(['google', 'github']);
```

### Authentication

```php
$authManager = app('laravilt.auth');

// Check authentication
if ($authManager->check()) {
    $user = $authManager->user();
}

// Use specific guard
$admin = $authManager->guard('admin')->user();
```

### Two-Factor Authentication

```php
$twoFactor = app(\Laravilt\Auth\Services\TwoFactorService::class);

// Enable 2FA
$data = $twoFactor->enable($user, 'totp');
// Returns: ['secret', 'qr_code', 'recovery_codes']

// Verify code
$isValid = $twoFactor->verify($user, $code, 'totp');
```

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag=laravilt-auth-config
```

## Testing

```bash
composer test          # Run tests
composer test-coverage # With coverage
composer analyse       # Static analysis
composer format        # Code formatting
```

## Implementation Details

<details>
<summary><strong>✅ Completed Components (Click to expand)</strong></summary>

**Core Architecture**
- AuthServiceProvider
- AuthManager
- AuthProvider (fluent builder)
- Contracts: AuthMethod, AuthProviderInterface, TwoFactorProvider

**Authentication Methods**
- EmailPasswordAuth, PhoneOTPAuth, SocialAuth
- PasswordlessAuth, WebAuthnAuth, TwoFactorAuth

**Services & Providers**
- OTPService, TwoFactorService, WebAuthnService
- TotpProvider, SmsProvider, EmailProvider

**Models & Database**
- SocialAccount, TwoFactorCode, PersonalAccessToken
- 4 Migrations (2FA, social, webauthn, codes)

**Configuration & Routing**
- config/laravilt-auth.php
- Complete route definitions
- testbench.yaml

**Translations**
- English (100+ strings)
- Arabic (100+ strings with RTL)

**Testing**
- 20 unit tests, 31 assertions
- PHPStan Level 5: 0 errors
- All tests passing
</details>

<details>
<summary><strong>📋 Pending Implementation (Click to expand)</strong></summary>

**Controllers (14 files)**
- Auth: Login, Register, VerifyEmail, PasswordReset, Social, OTP, WebAuthn, TwoFactor
- Profile: Profile, Password, TwoFactor, Sessions, Tokens, DeleteAccount

**Middleware (3 files)**
- Authenticate, EnsureEmailVerified, RequireTwoFactor

**Guards & Providers (3 files)**
- LaraviltGuard, LaraviltAuthProvider, CustomAuthProvider

**Notifications (4 files)**
- VerifyEmail, ResetPassword, TwoFactorCode, LoginNotification

**Commands (2 files)**
- InstallAuthCommand, GenerateAuthCommand

**Views (14 Blade + Vue files)**
- Auth views: login, register, verify-email, reset-password, forgot-password, two-factor, social/callback
- Profile views: show, edit, password, two-factor, sessions, tokens, delete-account

**JavaScript (7 files)**
- auth: login.js, register.js, two-factor.js, webauthn.js
- profile: edit.js, sessions.js, tokens.js

**CSS (1 file)**
- auth.css

**Additional**
- Route splitting (auth.php, profile.php)
- Database seeders
- Feature tests for all flows
- Comprehensive documentation
- Usage examples
</details>

## Next Steps

To complete full implementation:
1. Controllers: Implement all auth and profile controllers
2. Views: Create Blade templates with Vue components
3. JavaScript: Build interactive components
4. Middleware: Authentication middleware
5. Commands: Artisan commands for auth generation
6. Feature Tests: End-to-end testing
7. Documentation: Complete usage guides

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
