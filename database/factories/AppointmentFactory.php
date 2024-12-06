<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition()
    {
        return [
            'artist_id' => User::factory()->create(['role' => 'artist']),
            'client_id' => User::factory()->create(['role' => 'client']),
            'conversation_id' => Conversation::factory(),
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'google_event_id' => null,
        ];
    }
} 