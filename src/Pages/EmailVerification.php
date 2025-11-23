<?php

namespace Laravilt\Auth\Pages;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerification
{
    /**
     * Display the email verification notice.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $panel = \Laravilt\Panel\Facades\Panel::getCurrent();

        // If already verified, redirect to dashboard
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect($panel ? $panel->url('') : '/');
        }

        // Determine component path based on panel
        $component = $panel ? $panel->getPath().'/auth/VerifyEmail' : 'auth/VerifyEmail';

        return Inertia::render($component, [
            'status' => session('status'),
        ]);
    }

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $panel = \Laravilt\Panel\Facades\Panel::getCurrent();

        if ($request->user()->hasVerifiedEmail()) {
            return redirect($panel ? $panel->url('') : '/');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect($panel ? $panel->url('') : '/')->with('status', 'email-verified');
    }

    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            $panel = \Laravilt\Panel\Facades\Panel::getCurrent();

            return redirect($panel ? $panel->url('') : '/');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
