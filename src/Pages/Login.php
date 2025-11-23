<?php

namespace Laravilt\Auth\Pages;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Forms\Components\Checkbox;
use Laravilt\Panel\Pages\Page;

class Login extends Page
{
    protected static ?string $slug = 'login';

    protected static ?string $title = 'Login';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * Get the form schema for login.
     */
    protected function getSchema(): array
    {
        return [
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->autofocus()
                ->placeholder('Enter your email'),

            TextInput::make('password')
                ->label('Password')
                ->password()
                ->required()
                ->placeholder('Enter your password'),

            Checkbox::make('remember')
                ->label('Remember me'),
        ];
    }

    /**
     * Display the login page.
     */
    public function create(Request $request)
    {
        $panel = $this->getPanel();

        return Inertia::render('laravilt/AuthPage', [
            'page' => [
                'heading' => static::getTitle(),
                'schema' => collect($this->getSchema())->map->toLaraviltProps()->toArray(),
            ],
            'formAction' => $panel ? $panel->url('login') : '/login',
            'formMethod' => 'POST',
            'canResetPassword' => $panel ? $panel->hasPasswordReset() : true,
            'canRegister' => $panel ? $panel->hasRegistration() : true,
            'resetPasswordUrl' => $panel ? $panel->url('forgot-password') : '/forgot-password',
            'registerUrl' => $panel ? $panel->url('register') : '/register',
            'status' => session('status'),
        ]);
    }

    /**
     * Handle login request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => __('The provided credentials do not match our records.'),
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $panel = $this->getPanel();

        // Check if OTP is enabled and this is a new device
        if ($panel && $panel->hasOtp() && $this->isNewDevice($request)) {
            $user = $request->user();

            // Generate OTP code
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP in session
            session([
                'otp_code' => $otp,
                'otp_expiry' => now()->addMinutes(5),
                'otp_user_id' => $user->id,
                'otp_remember' => $request->boolean('remember'),
            ]);

            // Send OTP notification
            try {
                $user->notify(new \Laravilt\Auth\Notifications\OTPNotification($otp, 'login'));
            } catch (\Exception $e) {
                // Log error but don't block login
                \Illuminate\Support\Facades\Log::error('Failed to send OTP notification', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Log the user out temporarily until OTP is verified
            Auth::logout();

            // Redirect to OTP verification page
            return redirect($panel->url($panel->getOtpPath()))
                ->with('status', 'We detected a login from a new device. Please verify with the OTP code sent to your email.');
        }

        // Redirect to panel dashboard
        return redirect()->intended($panel ? $panel->url('') : '/');
    }

    /**
     * Check if this is a new device for the user.
     */
    protected function isNewDevice(Request $request): bool
    {
        // TODO: Implement device tracking logic
        // For now, always return false to disable device-based OTP
        // You would typically check against a user_devices table
        return false;
    }

    /**
     * Log the user out.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $panel = $this->getPanel();

        return redirect($panel ? $panel->url('login') : '/login');
    }
}
