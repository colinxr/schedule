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
            'instagram' => '@' . $this->faker->userName(),
            'phone' => $this->faker->phoneNumber(),
            'settings' => [
                'notifications' => [
                    'email' => true,
                    'push' => true,
                ],
            ],
        ];
    }

    public function withCustomSettings(array $settings): self
    {
        return $this->state(function (array $attributes) use ($settings) {
            return [
                'settings' => $settings,
            ];
        });
    }

    public function withoutSettings(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'settings' => null,
            ];
        });
    }
} 