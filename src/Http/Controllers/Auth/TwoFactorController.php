<?php

namespace Laravilt\Auth\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Laravilt\Auth\Services\TwoFactorService;

/**
 * TwoFactorController handles Two-Factor Authentication (2FA).
 *
 * Provides:
 * - 2FA setup and enabling
 * - 2FA disabling
 * - 2FA verification during login
 * - Recovery codes generation
 * - TOTP, SMS, and Email 2FA methods
 */
class TwoFactorController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected TwoFactorService $twoFactorService
    ) {}

    /**
     * Show the 2FA challenge form during login.
     */
    public function show(): View
    {
        $user = Auth::user();

        if (!$user || !$user->two_factor_enabled) {
            return redirect()->route('laravilt.auth.login');
        }

        return view('laravilt-auth::two-factor.challenge', [
            'method' => $user->two_factor_method ?? 'totp',
        ]);
    }

    /**
     * Verify 2FA code during login.
     */
    public function verify(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'recovery' => ['boolean'],
        ]);

        $user = Auth::user();

        if (!$user) {
            return redirect()->route('laravilt.auth.login');
        }

        $code = $request->code;
        $method = $request->boolean('recovery') ? 'recovery' : ($user->two_factor_method ?? 'totp');

        // Verify the code
        if (!$this->twoFactorService->verify($user, $code, $method)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Invalid or expired verification code',
                ], 422);
            }

            return back()->withErrors(['code' => 'Invalid or expired verification code']);
        }

        // Mark 2FA as passed in session
        $request->session()->put('2fa_verified', true);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication verified',
            ]);
        }

        return redirect()->intended(route('laravilt.auth.dashboard'));
    }

    /**
     * Show the 2FA setup page.
     */
    public function showSetupForm(): View
    {
        $user = Auth::user();

        return view('laravilt-auth::two-factor.setup', [
            'enabled' => $user->two_factor_enabled ?? false,
            'method' => $user->two_factor_method ?? null,
        ]);
    }

    /**
     * Enable 2FA for the authenticated user.
     */
    public function enable(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'method' => ['required', 'string', 'in:totp,sms,email'],
        ]);

        $user = $request->user();
        $method = $request->method;

        // Enable 2FA
        $data = $this->twoFactorService->enable($user, $method);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication enabled',
                'data' => $data,
            ]);
        }

        return back()->with('2fa_data', $data)->with('status', 'Two-factor authentication enabled');
    }

    /**
     * Confirm 2FA setup by verifying a code.
     */
    public function confirm(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();
        $code = $request->code;

        // Verify the code
        if (!$this->twoFactorService->verify($user, $code, $user->two_factor_method)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Invalid verification code',
                ], 422);
            }

            return back()->withErrors(['code' => 'Invalid verification code']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication confirmed',
            ]);
        }

        return back()->with('status', 'Two-factor authentication confirmed and activated');
    }

    /**
     * Disable 2FA for the authenticated user.
     */
    public function disable(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = $request->user();

        // Disable 2FA
        $this->twoFactorService->disable($user);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication disabled',
            ]);
        }

        return back()->with('status', 'Two-factor authentication disabled');
    }

    /**
     * Generate new recovery codes.
     */
    public function generateRecoveryCodes(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (!$user->two_factor_enabled) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Two-factor authentication is not enabled',
                ], 422);
            }

            return back()->withErrors(['error' => 'Two-factor authentication is not enabled']);
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes($user);

        if ($request->expectsJson()) {
            return response()->json([
                'recovery_codes' => $recoveryCodes,
            ]);
        }

        return back()->with('recovery_codes', $recoveryCodes);
    }

    /**
     * Send 2FA code via SMS or email.
     */
    public function sendCode(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'method' => ['required', 'string', 'in:sms,email'],
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $method = $request->method;

        // Send code
        $sent = $this->twoFactorService->sendCode($user, $method);

        if (!$sent) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to send verification code',
                ], 422);
            }

            return back()->withErrors(['error' => 'Failed to send verification code']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Verification code sent',
            ]);
        }

        return back()->with('status', 'Verification code sent to your ' . $method);
    }

    /**
     * Show recovery codes page.
     */
    public function showRecoveryCodes(): View|RedirectResponse
    {
        $user = Auth::user();

        if (!$user->two_factor_enabled) {
            return redirect()->route('laravilt.auth.two-factor.setup');
        }

        return view('laravilt-auth::two-factor.recovery-codes');
    }
}
