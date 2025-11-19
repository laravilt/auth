<?php

namespace Laravilt\Auth\Http\Controllers\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * ProfileController handles viewing and updating user profile information.
 *
 * Supports:
 * - Viewing user profile
 * - Updating profile information (name, email, phone)
 * - Avatar upload
 * - Profile privacy settings
 */
class ProfileController
{
    /**
     * Display the user's profile.
     */
    public function show(): View
    {
        return view('laravilt-auth::profile.show', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Display the profile edit form.
     */
    public function edit(): View
    {
        return view('laravilt-auth::profile.edit', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'max:2048'], // 2MB max
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $avatarPath;

            // Delete old avatar if exists
            if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
                \Storage::disk('public')->delete($user->avatar);
            }
        }

        // Check if email changed
        $emailChanged = $user->email !== $validated['email'];

        // Update user profile
        $user->update($validated);

        // If email changed and verification is required, mark as unverified
        if ($emailChanged && config('laravilt-auth.registration.email_verification', true)) {
            $user->update(['email_verified_at' => null]);
            $user->sendEmailVerificationNotification();

            return redirect()
                ->route('laravilt.profile.edit')
                ->with('status', 'profile-updated-email-verification-sent');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Profile updated successfully.',
                'user' => $user->fresh(),
            ]);
        }

        return redirect()
            ->route('laravilt.profile.edit')
            ->with('status', 'profile-updated');
    }

    /**
     * Update the user's profile privacy settings.
     */
    public function updatePrivacy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'profile_visibility' => ['required', 'in:public,private,friends'],
            'show_email' => ['boolean'],
            'show_phone' => ['boolean'],
            'allow_messages' => ['boolean'],
        ]);

        $user->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Privacy settings updated successfully.',
            ]);
        }

        return redirect()
            ->route('laravilt.profile.edit')
            ->with('status', 'privacy-updated');
    }

    /**
     * Remove the user's avatar.
     */
    public function removeAvatar(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
            \Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => null]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Avatar removed successfully.',
            ]);
        }

        return redirect()
            ->route('laravilt.profile.edit')
            ->with('status', 'avatar-removed');
    }
}
