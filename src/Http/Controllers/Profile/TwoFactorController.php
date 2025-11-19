<?php

namespace Laravilt\Auth\Http\Controllers\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravilt\Auth\Services\TwoFactorService;
use Laravilt\Auth\Services\WebAuthnService;

/**
 * TwoFactorController handles two-factor authentication settings.
 *
 * Supports:
 * - Enabling/disabling 2FA
 * - TOTP (Google Authenticator) setup
 * - SMS-based 2FA
 * - WebAuthn/Passkey setup
 * - Recovery codes generation and management
 */
class TwoFactorController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected TwoFactorService $twoFactorService,
        protected WebAuthnService $webAuthnService
    ) {}

    /**
     * Display the two-factor authentication settings.
     */
    public function index(): View
    {
        $user = Auth::user();

        return view('laravilt-auth::profile.two-factor', [
            'user' => $user,
            'twoFactorEnabled' => $user->two_factor_enabled ?? false,
            'twoFactorMethod' => $user->two_factor_method ?? null,
            'hasRecoveryCodes' => !empty($user->two_factor_recovery_codes),
        ]);
    }

    /**
     * Enable two-factor authentication.
     */
    public function enable(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'method' => ['required', 'in:totp,sms,email,webauthn'],
        ]);

        $method = $request->input('method');

        // Enable 2FA
        $result = $this->twoFactorService->enable($user, $method);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication enabled successfully.',
                'data' => $result,
            ]);
        }

        // Store QR code and recovery codes in session for display
        if ($method === 'totp') {
            session([
                'two_factor_qr_code' => $result['qr_code'],
                'two_factor_secret' => $result['secret'],
                'two_factor_recovery_codes' => $result['recovery_codes'],
            ]);
        }

        return redirect()
            ->route('laravilt.profile.two-factor.index')
            ->with('status', 'two-factor-enabled');
    }

    /**
     * Confirm two-factor authentication setup.
     */
    public function confirm(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        // Verify the code
        if (!$this->twoFactorService->verify($user, $request->code)) {
            throw ValidationException::withMessages([
                'code' => __('The provided two-factor authentication code is invalid.'),
            ]);
        }

        // Clear session data
        session()->forget(['two_factor_qr_code', 'two_factor_secret']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication confirmed successfully.',
            ]);
        }

        return redirect()
            ->route('laravilt.profile.two-factor.index')
            ->with('status', 'two-factor-confirmed');
    }

    /**
     * Disable two-factor authentication.
     */
    public function disable(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        // Verify password before disabling
        if (!\Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('The provided password is incorrect.'),
            ]);
        }

        // Disable 2FA
        $this->twoFactorService->disable($user);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Two-factor authentication disabled successfully.',
            ]);
        }

        return redirect()
            ->route('laravilt.profile.two-factor.index')
            ->with('status', 'two-factor-disabled');
    }

    /**
     * Show recovery codes.
     */
    public function showRecoveryCodes(): View
    {
        $user = Auth::user();

        $recoveryCodes = [];
        if ($user->two_factor_recovery_codes) {
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        }

        return view('laravilt-auth::profile.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        if (!$user->two_factor_enabled) {
            throw ValidationException::withMessages([
                'two_factor' => __('Two-factor authentication is not enabled.'),
            ]);
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes($user);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Recovery codes regenerated successfully.',
                'recovery_codes' => $recoveryCodes,
            ]);
        }

        return redirect()
            ->route('laravilt.profile.two-factor.recovery-codes')
            ->with([
                'status' => 'recovery-codes-regenerated',
                'recovery_codes' => $recoveryCodes,
            ]);
    }

    /**
     * Generate WebAuthn registration options.
     */
    public function webauthnRegisterOptions(Request $request): JsonResponse
    {
        $user = Auth::user();

        $options = $this->webAuthnService->generateRegistrationOptions($user);

        // Store challenge in session for verification
        session(['webauthn_challenge' => $options['challenge']]);

        return response()->json($options);
    }

    /**
     * Register a WebAuthn credential.
     */
    public function webauthnRegister(Request $request): JsonResponse
    {
        $user = Auth::user();

        $request->validate([
            'credential' => ['required', 'array'],
        ]);

        // Verify and store the credential
        $this->webAuthnService->register($user, $request->credential);

        // Clear challenge from session
        session()->forget('webauthn_challenge');

        return response()->json([
            'message' => 'WebAuthn credential registered successfully.',
        ]);
    }
}
