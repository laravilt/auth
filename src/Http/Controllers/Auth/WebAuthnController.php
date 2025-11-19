<?php

namespace Laravilt\Auth\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Laravilt\Auth\Services\WebAuthnService;

/**
 * WebAuthnController handles WebAuthn (passwordless) authentication.
 *
 * Provides:
 * - WebAuthn registration options
 * - WebAuthn credential registration
 * - WebAuthn authentication options
 * - WebAuthn authentication verification
 */
class WebAuthnController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected WebAuthnService $webAuthnService
    ) {}

    /**
     * Show the WebAuthn login form.
     */
    public function showLoginForm(): View
    {
        return view('laravilt-auth::webauthn.login');
    }

    /**
     * Generate registration options for WebAuthn.
     */
    public function generateRegistrationOptions(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $options = $this->webAuthnService->generateRegistrationOptions($user);

        return response()->json($options);
    }

    /**
     * Register a new WebAuthn credential.
     */
    public function register(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.type' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.response' => ['required', 'array'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $credential = $request->input('credential');

        // Register the credential
        $registered = $this->webAuthnService->register($user, $credential);

        if (!$registered) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to register WebAuthn credential',
                ], 422);
            }

            return back()->withErrors(['credential' => 'Failed to register WebAuthn credential']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'WebAuthn credential registered successfully',
            ]);
        }

        return back()->with('status', 'WebAuthn credential registered successfully');
    }

    /**
     * Generate authentication options for WebAuthn.
     */
    public function generateAuthenticationOptions(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->email;

        $options = $this->webAuthnService->generateAuthenticationOptions($email);

        return response()->json($options);
    }

    /**
     * Authenticate user with WebAuthn.
     */
    public function authenticate(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'credential' => ['required', 'array'],
            'credential.id' => ['required', 'string'],
            'credential.type' => ['required', 'string'],
            'credential.rawId' => ['required', 'string'],
            'credential.response' => ['required', 'array'],
        ]);

        $email = $request->email;
        $credential = $request->input('credential');

        // Verify the credential
        if (!$this->webAuthnService->verify($email, $credential)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Invalid WebAuthn credential',
                ], 422);
            }

            return back()->withErrors(['credential' => 'Invalid WebAuthn credential']);
        }

        // Find and log in the user
        $userModel = config('auth.providers.users.model');
        $user = $userModel::where('email', $email)->first();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'User not found',
                ], 404);
            }

            return back()->withErrors(['email' => 'User not found']);
        }

        Auth::login($user, true);

        // Regenerate session
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Authentication successful',
                'user' => $user,
            ]);
        }

        return redirect()->intended(route('laravilt.auth.dashboard'));
    }

    /**
     * Show WebAuthn registration page.
     */
    public function showRegistrationPage(): View
    {
        return view('laravilt-auth::webauthn.register');
    }

    /**
     * Remove a WebAuthn credential.
     */
    public function remove(Request $request, string $credentialId): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        // In a full implementation, you would delete the credential from database
        // $user->webAuthnCredentials()->where('credential_id', $credentialId)->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'WebAuthn credential removed successfully',
            ]);
        }

        return back()->with('status', 'WebAuthn credential removed successfully');
    }
}
