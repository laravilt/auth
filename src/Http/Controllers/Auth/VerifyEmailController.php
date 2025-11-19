<?php

namespace Laravilt\Auth\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

/**
 * VerifyEmailController handles email verification.
 *
 * Provides:
 * - Email verification notice
 * - Email verification handler
 * - Resend verification email
 */
class VerifyEmailController
{
    /**
     * Display the email verification notice.
     */
    public function show(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user && $user->hasVerifiedEmail()) {
            return redirect()->route('laravilt.auth.dashboard');
        }

        return view('laravilt-auth::verify-email');
    }

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('laravilt.auth.dashboard');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('laravilt.auth.dashboard')->with('verified', true);
    }

    /**
     * Resend the email verification notification.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('laravilt.auth.dashboard');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('resent', true);
    }
}
