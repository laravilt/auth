<?php

namespace Laravilt\Auth\Pages;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravilt\Forms\Components\PinInput;
use Laravilt\Panel\Pages\Page;

class OTP extends Page
{
    protected static ?string $slug = 'otp';

    protected static ?string $title = 'OTP Verification';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * Get the form schema for OTP.
     */
    protected function getSchema(): array
    {
        return [
            PinInput::make('code')
                ->label('Verification Code')
                ->length(6)
                ->otp(true)
                ->type('numeric')
                ->required()
                ->autofocus(),
        ];
    }

    /**
     * Display the OTP verification form.
     */
    public function create(Request $request)
    {
        $panel = $this->getPanel();

        return Inertia::render('laravilt/AuthPage', [
            'page' => [
                'heading' => static::getTitle(),
                'subheading' => 'Enter the 6-digit code sent to your email',
                'schema' => collect($this->getSchema())->map->toLaraviltProps()->toArray(),
            ],
            'formAction' => $panel ? $panel->url('otp') : '/otp',
            'formMethod' => 'POST',
            'status' => session('status'),
            'hasOtpInSession' => session()->has('otp_code'),
        ]);
    }

    /**
     * Verify the OTP code.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $panel = $this->getPanel();

        // Get the OTP from session
        $sessionOtp = session('otp_code');
        $sessionExpiry = session('otp_expiry');

        // Verify OTP exists and hasn't expired
        if (! $sessionOtp || ! $sessionExpiry || now()->isAfter($sessionExpiry)) {
            return back()->withErrors([
                'code' => 'The OTP code has expired. Please request a new one.',
            ]);
        }

        // Verify the code matches
        if ($request->code !== $sessionOtp) {
            return back()->withErrors([
                'code' => 'The OTP code is incorrect.',
            ]);
        }

        // Check if this is a registration flow
        $registrationData = session('registration_data');
        if ($registrationData) {
            // Complete the registration
            $user = \App\Models\User::create([
                'name' => $registrationData['name'],
                'email' => $registrationData['email'],
                'password' => \Illuminate\Support\Facades\Hash::make($registrationData['password']),
                'email_verified_at' => now(), // Mark email as verified since OTP was confirmed
            ]);

            event(new \Illuminate\Auth\Events\Registered($user));

            Auth::login($user);

            // Clear registration data and OTP from session
            session()->forget(['registration_data', 'otp_code', 'otp_expiry', 'otp_email']);

            return redirect($panel ? $panel->url('') : '/')->with('status', 'Registration completed successfully!');
        }

        // For login flow - log the user in
        $userId = session('otp_user_id');
        if ($userId) {
            Auth::loginUsingId($userId);

            // Clear OTP from session
            session()->forget(['otp_code', 'otp_expiry', 'otp_user_id', 'otp_remember']);
        }

        return redirect($panel ? $panel->url('') : '/');
    }

    /**
     * Resend the OTP code.
     */
    public function resend(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['nullable', 'email', 'exists:users,email'],
        ]);

        // Determine if this is a registration or login flow
        $registrationData = session('registration_data');
        $userId = session('otp_user_id');

        // If email provided, find the user and store their ID in session
        if ($request->has('email')) {
            $user = \App\Models\User::where('email', $request->email)->first();

            if (! $user) {
                return back()->withErrors([
                    'email' => 'No account found with that email address.',
                ]);
            }

            session(['otp_user_id' => $user->id]);
            $userId = $user->id;
        }

        // Generate new OTP
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store in session (expires in 5 minutes)
        session([
            'otp_code' => $otp,
            'otp_expiry' => now()->addMinutes(5),
        ]);

        // Send OTP notification
        try {
            if ($registrationData) {
                // Registration flow - create temp user for notification
                $tempUser = new \App\Models\User([
                    'name' => $registrationData['name'],
                    'email' => $registrationData['email'],
                ]);
                $tempUser->notify(new \Laravilt\Auth\Notifications\OTPNotification($otp, 'registration'));
            } elseif ($userId) {
                // Login flow - send to existing user
                $user = \App\Models\User::find($userId);
                if ($user) {
                    $user->notify(new \Laravilt\Auth\Notifications\OTPNotification($otp, 'login'));
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to resend OTP notification', [
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('status', 'A new OTP code has been sent.');
    }
}
