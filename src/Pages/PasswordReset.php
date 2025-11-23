<?php

namespace Laravilt\Auth\Pages;

use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Forms\Components\Hidden;
use Laravilt\Panel\Pages\Page;

class PasswordReset extends Page
{
    protected static ?string $slug = 'forgot-password';

    protected static ?string $title = 'Reset Password';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * Get the form schema for forgot password.
     */
    protected function getForgotSchema(): array
    {
        return [
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->autofocus()
                ->placeholder('Enter your email'),
        ];
    }

    /**
     * Get the form schema for reset password.
     */
    protected function getResetSchema(string $email): array
    {
        return [
            TextInput::make('password')
                ->label('New Password')
                ->password()
                ->required()
                ->autofocus()
                ->placeholder('Enter new password'),

            TextInput::make('password_confirmation')
                ->label('Confirm Password')
                ->password()
                ->required()
                ->placeholder('Confirm new password'),
        ];
    }

    /**
     * Display the password reset request form.
     */
    public function create(Request $request)
    {
        $panel = $this->getPanel();

        return Inertia::render('laravilt/AuthPage', [
            'page' => [
                'heading' => 'Forgot Password',
                'subheading' => 'Enter your email to receive a password reset link',
                'schema' => collect($this->getForgotSchema())->map->toLaraviltProps()->toArray(),
            ],
            'formAction' => $panel ? $panel->url('forgot-password') : '/forgot-password',
            'formMethod' => 'POST',
            'canLogin' => $panel ? $panel->hasLogin() : true,
            'loginUrl' => $panel ? $panel->url('login') : '/login',
            'status' => session('status'),
        ]);
    }

    /**
     * Send a password reset link to the user.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Send the password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Display the password reset form.
     */
    public function edit(Request $request, string $token)
    {
        $panel = $this->getPanel();

        return Inertia::render('laravilt/AuthPage', [
            'page' => [
                'heading' => static::getTitle(),
                'subheading' => 'Enter your new password',
                'schema' => collect($this->getResetSchema($request->email))->map->toLaraviltProps()->toArray(),
            ],
            'formAction' => $panel ? $panel->url('reset-password') : '/reset-password',
            'formMethod' => 'POST',
            'hiddenFields' => [
                'token' => $token,
                'email' => $request->email,
            ],
        ]);
    }

    /**
     * Reset the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordResetEvent($user));
            }
        );

        $panel = $this->getPanel();

        return $status === Password::PASSWORD_RESET
            ? redirect($panel ? $panel->loginUrl() : '/login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
