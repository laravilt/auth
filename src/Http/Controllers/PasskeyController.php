<?php

namespace Laravilt\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;
use Laragear\WebAuthn\Http\Requests\AttestationRequest;
use Laragear\WebAuthn\Http\Requests\AttestedRequest;
use Laravilt\Auth\Events\PasskeyDeleted;
use Laravilt\Auth\Events\PasskeyRegistered;

class PasskeyController extends Controller
{
    /**
     * Generate attestation options for passkey registration.
     */
    public function registerOptions(AttestationRequest $request)
    {
        return $request->toCreate();
    }

    /**
     * Register a new passkey credential.
     */
    public function register(AttestedRequest $request)
    {
        $validated = $request->validated();

        // Save the credential - returns credential ID (string) in Laragear WebAuthn v4
        $credentialId = $request->save();

        // Get the credential model to update alias
        $user = Auth::user();
        $credential = $user->webAuthnCredentials()->where('id', $credentialId)->first();

        // Update credential alias with the user-provided name
        if ($credential && isset($validated['name'])) {
            $credential->update(['alias' => $validated['name']]);
        }

        // Get the panel from request attributes
        $panel = $request->attributes->get('panel');

        // Dispatch passkey registered event
        if ($panel) {
            PasskeyRegistered::dispatch(
                $user,
                $credentialId,
                $validated['name'] ?? 'Passkey',
                $panel->getId()
            );
        }

        // Flash success notification
        session()->flash('notifications', [[
            'type' => 'success',
            'message' => 'Passkey registered successfully!',
        ]]);

        return back();
    }

    /**
     * Delete a passkey credential.
     */
    public function destroy(Request $request, string $credentialId)
    {
        $user = Auth::user();

        $credential = $user->webAuthnCredentials()
            ->where('id', $credentialId)
            ->firstOrFail();

        $credential->delete();

        // Get the panel from request attributes
        $panel = $request->attributes->get('panel');

        // Dispatch passkey deleted event
        if ($panel) {
            PasskeyDeleted::dispatch(
                $user,
                $credentialId,
                $panel->getId()
            );
        }

        // Flash success notification
        session()->flash('notifications', [[
            'type' => 'success',
            'message' => 'Passkey deleted successfully!',
        ]]);

        return back();
    }

    /**
     * Generate assertion options for passkey login.
     */
    public function loginOptions(AssertionRequest $request)
    {
        // Get the user ID from the session (set during login challenge)
        $userId = $request->session()->get('login.id');

        if (! $userId) {
            return response()->json(['error' => 'No login session found'], 400);
        }

        // Get the panel from request attributes (set by IdentifyPanel middleware)
        $panel = $request->attributes->get('panel');

        if (! $panel) {
            return response()->json(['error' => 'Panel not found'], 404);
        }

        $guard = $panel->getAuthGuard() ?? 'web';
        $provider = config("auth.guards.{$guard}.provider");
        $modelClass = config("auth.providers.{$provider}.model");
        $user = $modelClass::find($userId);

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return $request->toVerify($user);
    }

    /**
     * Verify passkey assertion and log user in.
     */
    public function login(AssertedRequest $request)
    {
        // Get the panel from request attributes (set by IdentifyPanel middleware)
        $panel = $request->attributes->get('panel');

        if (! $panel) {
            return response()->json(['error' => 'Panel not found'], 404);
        }

        $guard = $panel->getAuthGuard() ?? 'web';
        $remember = $request->session()->get('login.remember', false);

        // Authenticate user via WebAuthn
        $user = $request->login($guard, $remember);

        if (! $user) {
            return response()->json(['error' => 'Authentication failed'], 401);
        }

        // Mark that 2FA has been completed in this session
        $request->session()->put('auth.two_factor_confirmed_at', now()->timestamp);

        // Return success with redirect URL
        return response()->json([
            'redirect' => url($panel->getPath()),
        ]);
    }
}
