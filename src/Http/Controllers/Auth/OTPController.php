<?php

namespace Laravilt\Auth\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Laravilt\Auth\Services\OTPService;
use Laravilt\Auth\Methods\PhoneOTPMethod;

/**
 * OTPController handles OTP (One-Time Password) operations.
 *
 * Provides:
 * - OTP sending to phone
 * - OTP verification
 * - OTP login form
 * - OTP resend functionality
 */
class OTPController
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected OTPService $otpService
    ) {}

    /**
     * Show the OTP login form.
     */
    public function showLoginForm(): View
    {
        return view('laravilt-auth::otp.login');
    }

    /**
     * Send OTP to the provided phone number.
     */
    public function send(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = $request->phone;

        // Check if phone exists
        $userModel = config('auth.providers.users.model');
        $user = $userModel::where('phone', $phone)->first();

        if (!$user) {
            return $this->respondWithError('Phone number not found');
        }

        // Send OTP
        $sent = $this->otpService->send($phone);

        if (!$sent) {
            return $this->respondWithError('Failed to send OTP');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'OTP sent successfully',
                'expires_in' => 5, // minutes
            ]);
        }

        return back()->with('status', 'OTP sent to your phone');
    }

    /**
     * Verify OTP and log the user in.
     */
    public function verify(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
            'remember' => ['boolean'],
        ]);

        $phone = $request->phone;
        $otp = $request->otp;

        // Verify OTP
        if (!$this->otpService->verify($phone, $otp)) {
            return $this->respondWithError('Invalid or expired OTP');
        }

        // Find user
        $userModel = config('auth.providers.users.model');
        $user = $userModel::where('phone', $phone)->first();

        if (!$user) {
            return $this->respondWithError('User not found');
        }

        // Log the user in
        Auth::login($user, $request->boolean('remember'));

        // Regenerate session
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
            ]);
        }

        return redirect()->intended(route('laravilt.auth.dashboard'));
    }

    /**
     * Resend OTP to the phone number.
     */
    public function resend(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
        ]);

        $phone = $request->phone;

        // Send OTP
        $sent = $this->otpService->send($phone);

        if (!$sent) {
            return $this->respondWithError('Failed to resend OTP');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'OTP resent successfully',
            ]);
        }

        return back()->with('status', 'OTP resent to your phone');
    }

    /**
     * Send OTP for registration.
     */
    public function sendForRegistration(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
        ]);

        $phone = $request->phone;

        // Send OTP
        $sent = $this->otpService->send($phone);

        if (!$sent) {
            return $this->respondWithError('Failed to send OTP');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'OTP sent successfully',
            ]);
        }

        return back()->with('status', 'OTP sent to your phone');
    }

    /**
     * Verify OTP for registration.
     */
    public function verifyForRegistration(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $phone = $request->phone;
        $otp = $request->otp;

        // Verify OTP
        if (!$this->otpService->verify($phone, $otp)) {
            return $this->respondWithError('Invalid or expired OTP');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'OTP verified successfully',
                'verified' => true,
            ]);
        }

        return back()->with('phone_verified', true);
    }

    /**
     * Respond with error.
     */
    protected function respondWithError(string $message): JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 422);
        }

        return back()->withErrors(['otp' => $message]);
    }
}
