<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('+1 day', '+1 month');
        $endDate = (clone $startDate)->modify('+2 hours');

        return [
            'conversation_id' => Conversation::factory(),
            'artist_id' => User::factory()->create(['role' => 'artist']),
            'client_id' => User::factory()->create(['role' => 'client']),
            'starts_at' => $startDate,
            'ends_at' => $endDate,
        ];
    }
} 