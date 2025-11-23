<?php

namespace Laravilt\Auth\Pages;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravilt\Forms\Components\TextInput;
use Laravilt\Panel\Pages\Page;

class Profile extends Page
{
    protected static ?string $slug = 'profile';

    protected static ?string $title = 'Profile';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * Get the profile form schema.
     */
    protected function getProfileSchema($user): array
    {
        return [
            TextInput::make('name')
                ->label('Name')
                ->required()
                ->autofocus()
                ->placeholder('Enter your name')
                ->default($user->name),

            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->placeholder('Enter your email')
                ->default($user->email),
        ];
    }

    /**
     * Get the password form schema.
     */
    protected function getPasswordSchema(): array
    {
        return [
            TextInput::make('current_password')
                ->label('Current Password')
                ->password()
                ->required()
                ->placeholder('Enter current password'),

            TextInput::make('password')
                ->label('New Password')
                ->password()
                ->required()
                ->placeholder('Enter new password'),

            TextInput::make('password_confirmation')
                ->label('Confirm Password')
                ->password()
                ->required()
                ->placeholder('Confirm new password'),
        ];
    }

    /**
     * Get the delete account schema.
     */
    protected function getDeleteSchema(): array
    {
        return [
            TextInput::make('password')
                ->label('Password')
                ->password()
                ->required()
                ->placeholder('Enter your password to confirm'),
        ];
    }

    /**
     * Display the profile edit form.
     */
    public function edit(Request $request)
    {
        $panel = $this->getPanel();
        $user = $request->user();

        return Inertia::render('laravilt/ProfilePage', [
            'page' => [
                'heading' => static::getTitle(),
                'profileSchema' => collect($this->getProfileSchema($user))->map->toLaraviltProps()->toArray(),
                'passwordSchema' => collect($this->getPasswordSchema())->map->toLaraviltProps()->toArray(),
                'deleteSchema' => collect($this->getDeleteSchema())->map->toLaraviltProps()->toArray(),
            ],
            'profileAction' => $panel ? $panel->url('profile') : '/profile',
            'passwordAction' => $panel ? $panel->url('password') : '/password',
            'deleteAction' => $panel ? $panel->url('profile') : '/profile',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
        ]);

        $user = $request->user();
        $user->fill($validated);

        // If email changed, mark as unverified
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('status', 'profile-updated');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $panel = $this->getPanel();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($panel ? $panel->url('') : '/');
    }
}
