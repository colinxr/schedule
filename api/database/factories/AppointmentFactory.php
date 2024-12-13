<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Appointment;
use App\Models\Conversation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'artist_id' => User::factory()->create(['role' => 'artist']),
            'client_id' => User::factory()->create(['role' => 'client']),
            'conversation_id' => Conversation::factory(),
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
            'google_event_id' => null,
            'status' => 'scheduled',
            'notes' => $this->faker->sentence(),
        ];
    }

    /**
     * Set the duration of the appointment in minutes
     */
    public function duration(int $minutes): static
    {
        return $this->state(function (array $attributes) use ($minutes) {
            $start = Carbon::parse($attributes['starts_at']);
            
            return [
                'ends_at' => $start->copy()->addMinutes($minutes),
            ];
        });
    }

    /**
     * Set the start time of the appointment
     */
    public function startsAt(string $dayAndTime): static
    {
        return $this->state(function (array $attributes) use ($dayAndTime) {
            $start = Carbon::parse($dayAndTime);
            $end = isset($attributes['ends_at']) 
                ? $start->copy()->addMinutes(
                    Carbon::parse($attributes['ends_at'])->diffInMinutes(Carbon::parse($attributes['starts_at']))
                )
                : $start->copy()->addHour();

            return [
                'starts_at' => $start,
                'ends_at' => $end,
            ];
        });
    }

    /**
     * Create an appointment for next occurrence of the specified day
     */
    public function onDay(string $day): static
    {
        return $this->state(function (array $attributes) use ($day) {
            $start = Carbon::parse($attributes['starts_at'])
                ->next($day);
            
            $duration = Carbon::parse($attributes['ends_at'])
                ->diffInMinutes(Carbon::parse($attributes['starts_at']));

            return [
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes($duration),
            ];
        });
    }
} 