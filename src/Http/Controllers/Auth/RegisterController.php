<?php

namespace Laravilt\Auth\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Auth\Events\Registered;

/**
 * RegisterController handles user registration.
 *
 * Supports:
 * - Standard email/password registration
 * - Phone number registration
 * - Email verification
 */
class RegisterController
{
    /**
     * Show the registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('laravilt-auth::register', [
            'requirePhone' => config('laravilt-auth.registration.require_phone', false),
            'requireEmailVerification' => config('laravilt-auth.registration.email_verification', true),
        ]);
    }

    /**
     * Handle a registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        $this->validateRegistration($request);

        $user = $this->createUser($request);

        event(new Registered($user));

        // Log the user in
        Auth::login($user);

        // Regenerate session
        $request->session()->regenerate();

        // Redirect based on email verification requirement
        if ($this->requiresEmailVerification()) {
            return redirect()->route('laravilt.auth.verification.notice');
        }

        return redirect()->route('laravilt.auth.dashboard');
    }

    /**
     * Validate the registration request.
     */
    protected function validateRegistration(Request $request): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];

        // Add phone validation if required
        if (config('laravilt-auth.registration.require_phone', false)) {
            $rules['phone'] = ['required', 'string', 'max:20', 'unique:users'];
        }

        // Add terms acceptance if required
        if (config('laravilt-auth.registration.require_terms', false)) {
            $rules['terms'] = ['accepted'];
        }

        $request->validate($rules);
    }

    /**
     * Create a new user instance.
     */
    protected function createUser(Request $request): mixed
    {
        $userModel = config('auth.providers.users.model');

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ];

        // Add phone if provided
        if ($request->has('phone')) {
            $data['phone'] = $request->phone;
        }

        // Auto-verify email if verification is disabled
        if (!$this->requiresEmailVerification()) {
            $data['email_verified_at'] = now();
        }

        return $userModel::create($data);
    }

    /**
     * Check if email verification is required.
     */
    protected function requiresEmailVerification(): bool
    {
        return config('laravilt-auth.registration.email_verification', true);
    }
}
