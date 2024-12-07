<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkSchedule;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $artist;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->artist = User::factory()->create(['role' => 'artist']);

        // Create a work schedule for Tuesday-Saturday, 11am-6pm EST
        WorkSchedule::factory()
            ->count(5)
            ->sequential(2) // Start from Tuesday (2)
            ->create([
                'user_id' => $this->artist->id,
                'start_time' => '11:00',
                'end_time' => '18:00'
            ]);
    }

    public function test_recommends_earliest_available_slot()
    {
        // Given the artist has some existing appointments
        Appointment::factory()
            ->for($this->artist, 'artist')
            ->duration(60) // 1 hour appointment
            ->startsAt('next Tuesday 11:00') // First slot on Tuesday
            ->create();

        // When we request the next available slot for a 2-hour appointment
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=120");

        // Then it should suggest Tuesday at 12:00 (after the 1-hour appointment)
        $response->assertStatus(200)
            ->assertJson([
                'available_slots' => [
                    [
                        'starts_at' => now()->next('Tuesday')->setTimeFromTimeString('12:00')->toDateTimeString(),
                        'ends_at' => now()->next('Tuesday')->setTimeFromTimeString('14:00')->toDateTimeString(),
                        'duration' => 120
                    ]
                ]
            ]);
    }

    public function test_handles_end_of_day_boundaries()
    {
        // Given the artist has an appointment that ends at closing time
        Appointment::factory()
            ->for($this->artist, 'artist')
            ->duration(120) // 2 hour appointment
            ->startsAt('next Tuesday 16:00') // 4pm - 6pm
            ->create();

        // When we request the next available slot for a 2-hour appointment
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=120");

        // Then it should suggest Wednesday at 11:00 (next day opening)
        $response->assertStatus(200)
            ->assertJson([
                'available_slots' => [
                    [
                        'starts_at' => now()->next('Wednesday')->setTimeFromTimeString('11:00')->toDateTimeString(),
                        'ends_at' => now()->next('Wednesday')->setTimeFromTimeString('13:00')->toDateTimeString(),
                        'duration' => 120
                    ]
                ]
            ]);
    }
} 