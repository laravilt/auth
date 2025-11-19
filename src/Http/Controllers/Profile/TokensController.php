<?php

namespace Laravilt\Auth\Http\Controllers\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravilt\Auth\Models\PersonalAccessToken;

/**
 * TokensController handles API token management using Laravel Sanctum.
 *
 * Supports:
 * - Viewing all API tokens
 * - Creating new tokens with abilities
 * - Revoking tokens
 * - Token expiration
 * - Last used tracking
 */
class TokensController
{
    /**
     * Display all API tokens for the user.
     */
    public function index(): View
    {
        $user = Auth::user();

        $tokens = $user->tokens()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at,
                    'expires_at' => $token->expires_at,
                    'created_at' => $token->created_at,
                    'is_expired' => $token->expires_at && $token->expires_at->isPast(),
                ];
            });

        return view('laravilt-auth::profile.tokens', [
            'tokens' => $tokens,
            'availableAbilities' => $this->getAvailableAbilities(),
        ]);
    }

    /**
     * Create a new API token.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        // Check token limit
        $maxTokens = config('laravilt-auth.api_tokens.max_per_user', 10);
        if ($user->tokens()->count() >= $maxTokens) {
            throw ValidationException::withMessages([
                'name' => __('You have reached the maximum number of API tokens (:max).', ['max' => $maxTokens]),
            ]);
        }

        // Set abilities (default to all if not specified)
        $abilities = $validated['abilities'] ?? ['*'];

        // Validate abilities
        $availableAbilities = $this->getAvailableAbilities();
        if (!in_array('*', $abilities)) {
            foreach ($abilities as $ability) {
                if (!in_array($ability, $availableAbilities)) {
                    throw ValidationException::withMessages([
                        'abilities' => __('Invalid ability: :ability', ['ability' => $ability]),
                    ]);
                }
            }
        }

        // Set expiration
        $expiresAt = null;
        if (isset($validated['expires_in_days'])) {
            $expiresAt = now()->addDays($validated['expires_in_days']);
        } elseif ($defaultExpiry = config('laravilt-auth.api_tokens.default_expiry_days')) {
            $expiresAt = now()->addDays($defaultExpiry);
        }

        // Create token
        $token = $user->createToken(
            $validated['name'],
            $abilities,
            $expiresAt
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'API token created successfully.',
                'token' => $token->plainTextToken,
                'token_id' => $token->accessToken->id,
                'expires_at' => $expiresAt,
            ], 201);
        }

        return redirect()
            ->route('laravilt.profile.tokens.index')
            ->with([
                'status' => 'token-created',
                'token' => $token->plainTextToken,
            ]);
    }

    /**
     * Update an API token's abilities.
     */
    public function update(Request $request, int $tokenId): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        $token = $user->tokens()->findOrFail($tokenId);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'abilities' => ['sometimes', 'array'],
            'abilities.*' => ['string'],
        ]);

        // Update name if provided
        if (isset($validated['name'])) {
            $token->update(['name' => $validated['name']]);
        }

        // Update abilities if provided
        if (isset($validated['abilities'])) {
            $availableAbilities = $this->getAvailableAbilities();

            if (!in_array('*', $validated['abilities'])) {
                foreach ($validated['abilities'] as $ability) {
                    if (!in_array($ability, $availableAbilities)) {
                        throw ValidationException::withMessages([
                            'abilities' => __('Invalid ability: :ability', ['ability' => $ability]),
                        ]);
                    }
                }
            }

            $token->update(['abilities' => $validated['abilities']]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'API token updated successfully.',
            ]);
        }

        return redirect()
            ->route('laravilt.profile.tokens.index')
            ->with('status', 'token-updated');
    }

    /**
     * Revoke an API token.
     */
    public function destroy(Request $request, int $tokenId): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        $token = $user->tokens()->findOrFail($tokenId);
        $token->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'API token revoked successfully.',
            ]);
        }

        return redirect()
            ->route('laravilt.profile.tokens.index')
            ->with('status', 'token-revoked');
    }

    /**
     * Revoke all API tokens.
     */
    public function destroyAll(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        // Verify password before revoking all
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!\Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('The provided password is incorrect.'),
            ]);
        }

        $count = $user->tokens()->count();
        $user->tokens()->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __(':count tokens revoked successfully.', ['count' => $count]),
            ]);
        }

        return redirect()
            ->route('laravilt.profile.tokens.index')
            ->with('status', 'all-tokens-revoked');
    }

    /**
     * Get available token abilities.
     */
    protected function getAvailableAbilities(): array
    {
        return config('laravilt-auth.api_tokens.abilities', [
            '*',
            'read',
            'write',
            'delete',
        ]);
    }

    /**
     * Show token details.
     */
    public function show(int $tokenId): JsonResponse
    {
        $user = Auth::user();

        $token = $user->tokens()->findOrFail($tokenId);

        return response()->json([
            'id' => $token->id,
            'name' => $token->name,
            'abilities' => $token->abilities,
            'last_used_at' => $token->last_used_at,
            'expires_at' => $token->expires_at,
            'created_at' => $token->created_at,
            'is_expired' => $token->expires_at && $token->expires_at->isPast(),
        ]);
    }
}
