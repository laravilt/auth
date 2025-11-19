<?php

namespace Laravilt\Auth\Http\Controllers\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * SessionsController handles user session management.
 *
 * Supports:
 * - Viewing active sessions
 * - Revoking individual sessions
 * - Logging out all other devices
 * - Session details (IP, device, location)
 */
class SessionsController
{
    /**
     * Display all active sessions for the user.
     */
    public function index(): View
    {
        $user = Auth::user();
        $sessions = $this->getUserSessions($user);

        return view('laravilt-auth::profile.sessions', [
            'sessions' => $sessions,
            'currentSessionId' => Session::getId(),
        ]);
    }

    /**
     * Get all sessions for the authenticated user.
     */
    protected function getUserSessions($user): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        $sessions = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'last_activity' => $session->last_activity,
                    'is_current' => $session->id === Session::getId(),
                    'device' => $this->parseUserAgent($session->user_agent),
                ];
            })
            ->all();

        return $sessions;
    }

    /**
     * Parse user agent to extract device information.
     */
    protected function parseUserAgent(string $userAgent): array
    {
        // Simple user agent parsing
        $device = [
            'browser' => 'Unknown Browser',
            'platform' => 'Unknown Platform',
            'device_type' => 'desktop',
        ];

        // Detect browser
        if (str_contains($userAgent, 'Chrome')) {
            $device['browser'] = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $device['browser'] = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            $device['browser'] = 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $device['browser'] = 'Edge';
        } elseif (str_contains($userAgent, 'Opera')) {
            $device['browser'] = 'Opera';
        }

        // Detect platform
        if (str_contains($userAgent, 'Windows')) {
            $device['platform'] = 'Windows';
        } elseif (str_contains($userAgent, 'Mac')) {
            $device['platform'] = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $device['platform'] = 'Linux';
        } elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            $device['platform'] = 'iOS';
            $device['device_type'] = 'mobile';
        } elseif (str_contains($userAgent, 'Android')) {
            $device['platform'] = 'Android';
            $device['device_type'] = 'mobile';
        }

        return $device;
    }

    /**
     * Revoke a specific session.
     */
    public function destroy(Request $request, string $sessionId): RedirectResponse|JsonResponse
    {
        $user = Auth::user();

        // Verify password before revoking
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!\Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('The provided password is incorrect.'),
            ]);
        }

        // Don't allow revoking current session
        if ($sessionId === Session::getId()) {
            throw ValidationException::withMessages([
                'session' => __('You cannot revoke your current session.'),
            ]);
        }

        // Delete the session from database
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('id', $sessionId)
                ->where('user_id', $user->id)
                ->delete();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Session revoked successfully.',
            ]);
        }

        return redirect()
            ->route('laravilt.profile.sessions.index')
            ->with('status', 'session-revoked');
    }

    /**
     * Logout from all other devices.
     */
    public function destroyOthers(Request $request): RedirectResponse|JsonResponse
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

        // Use Laravel's built-in method to logout other devices
        Auth::logoutOtherDevices($request->password);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'All other sessions have been logged out.',
            ]);
        }

        return redirect()
            ->route('laravilt.profile.sessions.index')
            ->with('status', 'sessions-logged-out');
    }

    /**
     * Get session details.
     */
    public function show(string $sessionId): JsonResponse
    {
        $user = Auth::user();

        if (config('session.driver') !== 'database') {
            return response()->json([
                'error' => 'Session details are only available when using database driver.',
            ], 400);
        }

        $session = DB::table(config('session.table', 'sessions'))
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json([
                'error' => 'Session not found.',
            ], 404);
        }

        return response()->json([
            'id' => $session->id,
            'ip_address' => $session->ip_address,
            'user_agent' => $session->user_agent,
            'last_activity' => $session->last_activity,
            'is_current' => $session->id === Session::getId(),
            'device' => $this->parseUserAgent($session->user_agent),
        ]);
    }
}
