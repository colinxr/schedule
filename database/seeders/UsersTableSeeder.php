<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // Create 5 artists
        for ($i = 1; $i <= 5; $i++) {
            $user = User::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'role' => 'artist',
                'email_verified_at' => now(),
            ]);

            Profile::create([
                'user_id' => $user->id,
                'instagram' => '@' . fake()->userName(),
                'phone' => fake()->phoneNumber(),
                'settings' => [
                    'notifications' => true,
                    'availability_reminder' => true,
                    'booking_confirmation' => true
                ]
            ]);
        }

        // Create 50 clients
        for ($i = 1; $i <= 50; $i++) {
            $user = User::create([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => Hash::make('password'),
                'role' => 'client',
                'email_verified_at' => now(),
            ]);

            Profile::create([
                'user_id' => $user->id,
                'instagram' => '@' . fake()->userName(),
                'phone' => fake()->phoneNumber(),
                'settings' => [
                    'notifications' => true,
                    'booking_reminder' => true
                ]
            ]);
        }
    }
} 