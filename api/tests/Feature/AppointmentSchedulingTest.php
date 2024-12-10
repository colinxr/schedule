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
    private Carbon $nextTuesday;
    private Carbon $nextWednesday;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->artist = User::factory()->create(['role' => 'artist']);

        // Set fixed dates for testing
        $this->nextTuesday = now()->next('Tuesday');
        $this->nextWednesday = now()->next('Wednesday');

        // Create work schedules for Tuesday-Saturday
        for ($day = 2; $day <= 6; $day++) {
            WorkSchedule::create([
                'user_id' => $this->artist->id,
                'day_of_week' => $day,
                'start_time' => '11:00',
                'end_time' => '18:00',
                'is_active' => true
            ]);
        }
    }

    public function test_recommends_earliest_available_slot()
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->artist);
        
        Appointment::factory()
            ->for($this->artist, 'artist')
            ->duration(120)
            ->create([
                'starts_at' => $this->nextTuesday->copy()->setTimeFromTimeString('11:00'),
                'ends_at' => $this->nextTuesday->copy()->setTimeFromTimeString('13:00')
            ]);

        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=120&date={$this->nextTuesday->format('Y-m-d')}");

        $response->assertStatus(200)
            ->assertJsonPath('available_slots.0', [
                'starts_at' => $this->nextTuesday->copy()->setTimeFromTimeString('13:00')->toDateTimeString(),
                'ends_at' => $this->nextTuesday->copy()->setTimeFromTimeString('15:00')->toDateTimeString(),
                'duration' => 120
            ]);
    }

    public function test_handles_end_of_day_boundaries()
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->artist);
        
        Appointment::factory()
            ->for($this->artist, 'artist')
            ->duration(120)
            ->create([
                'starts_at' => $this->nextTuesday->copy()->setTimeFromTimeString('16:00'),
                'ends_at' => $this->nextTuesday->copy()->setTimeFromTimeString('18:00')
            ]);

        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=120&date={$this->nextWednesday->format('Y-m-d')}");

        $response->assertStatus(200)
            ->assertJsonPath('available_slots.0', [
                'starts_at' => $this->nextWednesday->copy()->setTimeFromTimeString('11:00')->toDateTimeString(),
                'ends_at' => $this->nextWednesday->copy()->setTimeFromTimeString('13:00')->toDateTimeString(),
                'duration' => 120
            ]);
    }

    public function test_enforces_minimum_appointment_duration()
    {
        $this->actingAs($this->artist);
        
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=15");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['duration']);
    }

    public function test_can_request_slots_for_specific_date()
    {
        $this->actingAs($this->artist);
        
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&date={$this->nextTuesday->format('Y-m-d')}");

        $response->assertStatus(200)
            ->assertJsonPath('available_slots.0', [
                'starts_at' => $this->nextTuesday->copy()->setTimeFromTimeString('11:00')->toDateTimeString(),
                'ends_at' => $this->nextTuesday->copy()->setTimeFromTimeString('12:00')->toDateTimeString(),
                'duration' => 60
            ]);
    }

    public function test_returns_no_slots_for_non_working_days()
    {
        $this->actingAs($this->artist);
        
        $nextSunday = now()->next('Sunday')->format('Y-m-d');
        
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&date={$nextSunday}");

        $response->assertStatus(200)
            ->assertJson(['available_slots' => []]);
    }

    public function test_limits_number_of_returned_slots()
    {
        $this->actingAs($this->artist);
        
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&limit=3&date={$this->nextTuesday->format('Y-m-d')}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'available_slots');
    }
} 