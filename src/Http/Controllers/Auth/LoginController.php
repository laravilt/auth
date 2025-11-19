<?php

namespace Laravilt\Auth\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Laravilt\Auth\AuthManager;
use Laravilt\Auth\Methods\EmailPasswordMethod;
use Laravilt\Auth\Methods\PhoneOTPMethod;
use Laravilt\Auth\Methods\WebAuthnMethod;

/**
 * LoginController handles user authentication using multiple methods.
 *
 * Supports:
 * - Email/Password authentication
 * - Phone OTP authentication
 * - WebAuthn authentication
 * - Social OAuth authentication
 */
class LoginController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected AuthManager $authManager
    ) {}

    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('laravilt-auth::login', [
            'enabledMethods' => $this->getEnabledMethods(),
        ]);
    }

    /**
     * Handle login request.
     */
    public function login(Request $request): RedirectResponse
    {
        // Validate based on login type
        $this->validateLoginRequest($request);

        // Try to authenticate using the appropriate method
        $user = null;

        if ($request->has(['email', 'password'])) {
            $user = $this->attemptEmailPasswordLogin($request);
        } elseif ($request->has(['phone', 'otp'])) {
            $user = $this->attemptPhoneOTPLogin($request);
        } elseif ($request->has(['email', 'credential'])) {
            $user = $this->attemptWebAuthnLogin($request);
        }

        if (!$user) {
            return back()
                ->withInput($request->only('email', 'phone', 'remember'))
                ->withErrors([
                    'email' => __('These credentials do not match our records.'),
                ]);
        }

        // Regenerate session to prevent fixation attacks
        $request->session()->regenerate();

        // Set the authentication method
        $this->authManager->setCurrentMethod($this->getAuthMethodName($request));

        // Check if 2FA is required
        if ($this->requires2FA($user)) {
            return redirect()->route('laravilt.auth.two-factor.show');
        }

        return redirect()->intended(route('laravilt.auth.dashboard'));
    }

    /**
     * Attempt email/password login.
     */
    protected function attemptEmailPasswordLogin(Request $request): mixed
    {
        $method = app(EmailPasswordMethod::class);

        if ($method->validate($request)) {
            return $method->authenticate($request);
        }

        return null;
    }

    /**
     * Attempt phone OTP login.
     */
    protected function attemptPhoneOTPLogin(Request $request): mixed
    {
        $method = app(PhoneOTPMethod::class);

        if ($method->validate($request)) {
            return $method->authenticate($request);
        }

        return null;
    }

    /**
     * Attempt WebAuthn login.
     */
    protected function attemptWebAuthnLogin(Request $request): mixed
    {
        $method = app(WebAuthnMethod::class);

        if ($method->validate($request)) {
            return $method->authenticate($request);
        }

        return null;
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('laravilt.auth.login');
    }

    /**
     * Validate the login request based on provided fields.
     */
    protected function validateLoginRequest(Request $request): void
    {
        if ($request->has(['email', 'password'])) {
            $request->validate([
                'email' => ['required', 'email'],
                'password' => ['required', 'string'],
                'remember' => ['boolean'],
            ]);
        } elseif ($request->has(['phone', 'otp'])) {
            $request->validate([
                'phone' => ['required', 'string'],
                'otp' => ['required', 'string', 'size:6'],
                'remember' => ['boolean'],
            ]);
        } elseif ($request->has(['email', 'credential'])) {
            $request->validate([
                'email' => ['required', 'email'],
                'credential' => ['required', 'array'],
            ]);
        }
    }

    /**
     * Get the authentication method name from request.
     */
    protected function getAuthMethodName(Request $request): string
    {
        if ($request->has(['email', 'password'])) {
            return 'email';
        } elseif ($request->has(['phone', 'otp'])) {
            return 'phone';
        } elseif ($request->has(['email', 'credential'])) {
            return 'webauthn';
        }

        return 'unknown';
    }

    /**
     * Check if user requires 2FA.
     */
    protected function requires2FA(mixed $user): bool
    {
        return method_exists($user, 'two_factor_enabled') && $user->two_factor_enabled;
    }

    /**
     * Get enabled authentication methods.
     */
    protected function getEnabledMethods(): array
    {
        return [
            'email' => true,
            'phone' => config('laravilt-auth.methods.phone.enabled', false),
            'webauthn' => config('laravilt-auth.methods.webauthn.enabled', false),
            'social' => config('laravilt-auth.methods.social.enabled', false),
        ];
    }
}
