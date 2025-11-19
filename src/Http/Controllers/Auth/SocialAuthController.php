<?php

namespace Laravilt\Auth\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravilt\Auth\Models\SocialAccount;
use Illuminate\Auth\Events\Registered;

/**
 * SocialAuthController handles social OAuth authentication.
 *
 * Supports:
 * - OAuth redirect to provider
 * - OAuth callback handling
 * - Social account linking
 * - Auto-registration via social login
 */
class SocialAuthController
{
    /**
     * Redirect to social provider for authentication.
     */
    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle callback from social provider.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('laravilt.auth.login')
                ->withErrors(['error' => 'Failed to authenticate with ' . ucfirst($provider)]);
        }

        // Find or create user
        $user = $this->findOrCreateUser($socialUser, $provider);

        if (!$user) {
            return redirect()->route('laravilt.auth.login')
                ->withErrors(['error' => 'Unable to create user account']);
        }

        // Log the user in
        Auth::login($user, true);

        // Regenerate session
        $request->session()->regenerate();

        return redirect()->intended(route('laravilt.auth.dashboard'));
    }

    /**
     * Find or create user from social provider data.
     */
    protected function findOrCreateUser($socialUser, string $provider): mixed
    {
        $userModel = config('auth.providers.users.model');

        // Check if social account already exists
        $socialAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            // Update social account data
            $socialAccount->update([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
                'token' => $socialUser->token,
                'refresh_token' => $socialUser->refreshToken ?? null,
            ]);

            return $socialAccount->user;
        }

        // Check if user exists with same email
        $user = $userModel::where('email', $socialUser->getEmail())->first();

        if (!$user) {
            // Create new user
            $user = $userModel::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
                'email_verified_at' => now(), // Auto-verify email from social
            ]);

            event(new Registered($user));
        }

        // Create social account
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'name' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'avatar' => $socialUser->getAvatar(),
            'token' => $socialUser->token,
            'refresh_token' => $socialUser->refreshToken ?? null,
        ]);

        return $user;
    }

    /**
     * Validate the social provider.
     */
    protected function validateProvider(string $provider): void
    {
        $allowedProviders = config('laravilt-auth.social.providers', ['google', 'github', 'facebook']);

        if (!in_array($provider, $allowedProviders)) {
            abort(404, 'Social provider not supported');
        }
    }

    /**
     * Unlink a social account.
     */
    public function unlink(Request $request, string $provider): RedirectResponse
    {
        $user = $request->user();

        $socialAccount = SocialAccount::where('user_id', $user->id)
            ->where('provider', $provider)
            ->first();

        if ($socialAccount) {
            $socialAccount->delete();

            return back()->with('status', 'Social account unlinked successfully');
        }

        return back()->withErrors(['error' => 'Social account not found']);
    }
}
