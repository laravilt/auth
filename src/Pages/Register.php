<?php

namespace Laravilt\Auth\Pages;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Panel\Pages\Page;

class Register extends Page
{
    protected static ?string $slug = 'register';

    protected static ?string $title = 'Register';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * Get the form schema for registration.
     */
    protected function getSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->autofocus()
                ->placeholder('Enter your name')
                ->maxLength(255),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->placeholder('Enter your email')
                ->maxLength(255),

            TextInput::make('password')
                ->label('Password')
                ->password()
                ->required()
                ->placeholder('Enter your password'),

            TextInput::make('password_confirmation')
                ->label('Confirm Password')
                ->password()
                ->required()
                ->placeholder('Confirm your password'),
        ];
    }

    /**
     * Display the registration page.
     */
    public function create(Request $request)
    {
        $panel = $this->getPanel();

        return Inertia::render('laravilt/AuthPage', [
            'page' => [
                'heading' => static::getTitle(),
                'subheading' => 'Create a new account',
                'schema' => collect($this->getSchema())->map->toLaraviltProps()->toArray(),
            ],
            'formAction' => $panel ? $panel->url('register') : '/register',
            'formMethod' => 'POST',
            'canLogin' => $panel ? $panel->hasLogin() : true,
            'loginUrl' => $panel ? $panel->url('login') : '/login',
        ]);
    }

    /**
     * Handle registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $panel = $this->getPanel();

        // If OTP is enabled, store registration data in session and redirect to OTP
        if ($panel && $panel->hasOtp()) {
            // Generate OTP code
            $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store registration data and OTP in session
            session([
                'registration_data' => [
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => $request->password,
                ],
                'otp_code' => $otp,
                'otp_expiry' => now()->addMinutes(5),
                'otp_email' => $request->email,
            ]);

            // Create a temporary user object for sending notification
            $tempUser = new User([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            // Send OTP notification
            try {
                $tempUser->notify(new \Laravilt\Auth\Notifications\OTPNotification($otp, 'registration'));
            } catch (\Exception $e) {
                // Log error but don't block registration
                \Illuminate\Support\Facades\Log::error('Failed to send OTP notification', [
                    'email' => $request->email,
                    'error' => $e->getMessage(),
                ]);
            }

            // Redirect to OTP verification page
            return redirect($panel->url($panel->getOtpPath()))
                ->with('status', 'An OTP code has been sent to your email. Please verify to complete registration.');
        }

        // Create user without OTP
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Redirect to panel dashboard
        return redirect($panel ? $panel->url('') : '/');
    }
}
