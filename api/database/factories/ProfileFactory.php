<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'instagram' => fake()->userName(),
            'phone' => fake()->phoneNumber(),
            'settings' => [
                'deposit_percentage' => 30,
            ],
        ];
    }

    /**
     * Create a profile without settings
     */
    public function withoutSettings()
    {
        return $this->state(fn (array $attributes) => [
            'settings' => null,
        ]);
    }

    /**
     * Create a profile with custom settings
     */
    public function withCustomSettings(array $settings)
    {
        return $this->state(fn (array $attributes) => [
            'settings' => $settings,
        ]);
    }
} 