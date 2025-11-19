<?php

namespace Laravilt\Auth\Http\Controllers\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * DeleteAccountController handles permanent account deletion.
 *
 * Supports:
 * - Account deletion confirmation
 * - Password verification
 * - Data cleanup (sessions, tokens, social accounts)
 * - Grace period for account recovery
 * - Soft delete option
 */
class DeleteAccountController
{
    /**
     * Display the account deletion confirmation page.
     */
    public function show(): View
    {
        return view('laravilt-auth::profile.delete-account', [
            'user' => Auth::user(),
            'hasSoftDelete' => $this->hasSoftDelete(),
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        // Verify password
        $request->validate([
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'accepted'],
        ]);

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('The provided password is incorrect.'),
            ]);
        }

        // Log account deletion
        Log::info('User account deleted', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
        ]);

        // Clean up related data
        $this->cleanupUserData($user);

        // Logout the user
        Auth::logout();

        // Invalidate session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Delete or soft delete the user
        if ($this->hasSoftDelete()) {
            $user->delete(); // Soft delete
        } else {
            $user->forceDelete(); // Hard delete
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your account has been deleted successfully.',
            ]);
        }

        return redirect()
            ->route('laravilt.auth.login')
            ->with('status', 'account-deleted');
    }

    /**
     * Schedule account deletion (with grace period).
     */
    public function schedule(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        // Verify password
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('The provided password is incorrect.'),
            ]);
        }

        // Get grace period from config (default 30 days)
        $graceDays = config('laravilt-auth.account_deletion.grace_period_days', 30);
        $deletionDate = now()->addDays($graceDays);

        // Mark account for deletion
        $user->update([
            'deletion_scheduled_at' => $deletionDate,
        ]);

        // Log scheduled deletion
        Log::info('User account deletion scheduled', [
            'user_id' => $user->id,
            'email' => $user->email,
            'deletion_date' => $deletionDate,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Your account is scheduled for deletion on :date.', [
                    'date' => $deletionDate->format('F j, Y'),
                ]),
                'deletion_date' => $deletionDate,
            ]);
        }

        return redirect()
            ->route('laravilt.profile.edit')
            ->with([
                'status' => 'account-deletion-scheduled',
                'deletion_date' => $deletionDate,
            ]);
    }

    /**
     * Cancel scheduled account deletion.
     */
    public function cancelScheduled(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        if (!$user->deletion_scheduled_at) {
            throw ValidationException::withMessages([
                'account' => __('No account deletion is scheduled.'),
            ]);
        }

        // Cancel deletion
        $user->update([
            'deletion_scheduled_at' => null,
        ]);

        // Log cancellation
        Log::info('User account deletion cancelled', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Account deletion cancelled successfully.',
            ]);
        }

        return redirect()
            ->route('laravilt.profile.edit')
            ->with('status', 'deletion-cancelled');
    }

    /**
     * Clean up all user-related data before deletion.
     */
    protected function cleanupUserData($user): void
    {
        DB::beginTransaction();

        try {
            // Revoke all API tokens
            $user->tokens()->delete();

            // Delete all sessions
            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)
                    ->delete();
            }

            // Delete social accounts
            if (method_exists($user, 'socialAccounts')) {
                $user->socialAccounts()->delete();
            }

            // Delete two-factor codes
            DB::table('two_factor_codes')
                ->where('user_id', $user->id)
                ->delete();

            // Delete user's uploaded avatar
            if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
                \Storage::disk('public')->delete($user->avatar);
            }

            // Allow custom cleanup via event
            event(new \Illuminate\Auth\Events\AccountDeleting($user));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error cleaning up user data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Export user data before deletion (GDPR compliance).
     */
    public function export(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Verify password
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('The provided password is incorrect.'),
            ]);
        }

        // Collect user data
        $data = [
            'personal_information' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
                'created_at' => $user->created_at,
                'email_verified_at' => $user->email_verified_at,
            ],
            'tokens' => $user->tokens()->get()->map(fn($token) => [
                'name' => $token->name,
                'abilities' => $token->abilities,
                'created_at' => $token->created_at,
                'last_used_at' => $token->last_used_at,
            ]),
            'social_accounts' => method_exists($user, 'socialAccounts')
                ? $user->socialAccounts()->get()->map(fn($account) => [
                    'provider' => $account->provider,
                    'created_at' => $account->created_at,
                ])
                : [],
            'security' => [
                'two_factor_enabled' => $user->two_factor_enabled ?? false,
                'two_factor_method' => $user->two_factor_method ?? null,
            ],
        ];

        // Log data export
        Log::info('User data exported', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return response()->json([
            'message' => 'User data exported successfully.',
            'data' => $data,
            'exported_at' => now(),
        ]);
    }

    /**
     * Check if the user model uses soft deletes.
     */
    protected function hasSoftDelete(): bool
    {
        $userModel = config('auth.providers.users.model');

        return in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive($userModel)
        );
    }
}
