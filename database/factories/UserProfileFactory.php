<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'instagram' => '@' . $this->faker->userName(),
            'phone' => $this->faker->phoneNumber(),
            'settings' => [
                'notifications' => [
                    'email' => true,
                    'push' => true,
                ],
                'theme' => 'light',
            ],
        ];
    }

    public function withoutSettings(): self
    {
        return $this->state(fn (array $attributes) => [
            'settings' => null,
        ]);
    }

    public function withCustomSettings(array $settings): self
    {
        return $this->state(fn (array $attributes) => [
            'settings' => $settings,
        ]);
    }
} 