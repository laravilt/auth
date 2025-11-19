<?php

namespace Laravilt\Auth\Http\Controllers\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * PasswordController handles password updates for authenticated users.
 *
 * Supports:
 * - Viewing password change form
 * - Updating password with current password verification
 * - Password strength requirements
 * - Session invalidation on password change
 */
class PasswordController
{
    /**
     * Display the password update form.
     */
    public function edit(): View
    {
        return view('laravilt-auth::profile.password', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('The provided password does not match your current password.'),
            ]);
        }

        // Check if new password is different from current
        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('The new password must be different from your current password.'),
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Invalidate other sessions if enabled
        if (config('laravilt-auth.security.logout_other_devices_on_password_change', true)) {
            Auth::logoutOtherDevices($validated['current_password']);
        }

        // Regenerate session token
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password updated successfully.',
            ]);
        }

        return redirect()
            ->route('laravilt.profile.password.edit')
            ->with('status', 'password-updated');
    }

    /**
     * Confirm the user's password before allowing sensitive operations.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('The provided password does not match your current password.'),
            ]);
        }

        // Store confirmation timestamp in session
        $request->session()->put('auth.password_confirmed_at', time());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password confirmed successfully.',
            ]);
        }

        return redirect()->intended(route('laravilt.profile.edit'));
    }

    /**
     * Show the password confirmation form.
     */
    public function showConfirm(): View
    {
        return view('laravilt-auth::profile.confirm-password');
    }
}
