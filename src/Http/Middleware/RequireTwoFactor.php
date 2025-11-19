<?php

namespace Laravilt\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireTwoFactor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('laravilt.auth.login');
        }

        // Check if user has 2FA enabled
        if ($user->two_factor_enabled ?? false) {
            // Check if 2FA is verified in session
            if (! $request->session()->get('2fa_verified', false)) {
                return redirect()->route('laravilt.auth.two-factor.show');
            }
        }

        return $next($request);
    }
}
