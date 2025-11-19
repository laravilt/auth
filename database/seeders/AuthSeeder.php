<?php

namespace Laravilt\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AuthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        if (! class_exists($userModel)) {
            $this->command->warn("User model {$userModel} does not exist. Skipping seeding.");
            return;
        }

        // Create a test user if it doesn't exist
        $user = $userModel::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Test user created: test@example.com / password");

        // Create admin user if it doesn't exist
        $admin = $userModel::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Admin user created: admin@example.com / password");

        // Optionally seed social accounts if the model exists
        if (class_exists(\Laravilt\Auth\Models\SocialAccount::class)) {
            \Laravilt\Auth\Models\SocialAccount::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => 'github',
                ],
                [
                    'provider_id' => '123456',
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'avatar' => 'https://github.com/images/error/octocat_happy.gif',
                    'token' => 'gho_' . bin2hex(random_bytes(16)),
                ]
            );

            $this->command->info("Sample social account created for test user");
        }
    }
}
