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
        $this->withoutExceptionHandling();
        $this->actingAs($this->artist);
        
        Appointment::factory()
            ->for($this->artist, 'artist')
            ->duration(60)
            ->startsAt('next Tuesday 11:00')
            ->create();

        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=120");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'starts_at' => now()->next('Tuesday')->setTimeFromTimeString('12:00')->toDateTimeString(),
                'ends_at' => now()->next('Tuesday')->setTimeFromTimeString('14:00')->toDateTimeString(),
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
            ->startsAt('next Tuesday 16:00')
            ->create();

        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=120");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'starts_at' => now()->next('Wednesday')->setTimeFromTimeString('11:00')->toDateTimeString(),
                'ends_at' => now()->next('Wednesday')->setTimeFromTimeString('13:00')->toDateTimeString(),
                'duration' => 120
            ]);
    }

    public function test_prevents_overlapping_appointments()
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->artist);
        
        // Given an existing 2-hour appointment from 1pm to 3pm
        $existingAppointment = Appointment::factory()
            ->for($this->artist, 'artist')
            ->duration(120)
            ->startsAt('next Tuesday 13:00')
            ->create();

        // When requesting slots for a 90-minute appointment
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=90");

        $response->assertStatus(200);
        
        // Get the response data
        $slots = collect($response->json('available_slots'));
        
        // Verify no slots start during the existing appointment
        $existingStart = Carbon::parse($existingAppointment->starts_at);
        $existingEnd = Carbon::parse($existingAppointment->ends_at);
        
        // Assert no slots overlap with existing appointment
        $overlappingSlots = $slots->filter(function ($slot) use ($existingStart, $existingEnd) {
            $slotStart = Carbon::parse($slot['starts_at']);
            $slotEnd = Carbon::parse($slot['ends_at']);
            
            return $slotStart->between($existingStart, $existingEnd) ||
                $slotEnd->between($existingStart, $existingEnd) ||
                ($slotStart->lte($existingStart) && $slotEnd->gte($existingEnd));
        });
        
        $this->assertTrue(
            $overlappingSlots->isEmpty(),
            'Found slots that overlap with existing appointment: ' . 
            $overlappingSlots->pluck('starts_at')->join(', ')
        );
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
        
        $nextTuesday = now()->next('Tuesday')->format('Y-m-d');
        
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&date={$nextTuesday}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'starts_at' => now()->next('Tuesday')->setTimeFromTimeString('11:00')->toDateTimeString(),
                'ends_at' => now()->next('Tuesday')->setTimeFromTimeString('12:00')->toDateTimeString(),
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
        
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&limit=3");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'available_slots');
    }

    public function test_respects_buffer_time_between_appointments()
    {
        $this->actingAs($this->artist);
        
        // Given an appointment that ends at 1pm
        Appointment::factory()
            ->for($this->artist, 'artist')
            ->duration(60)
            ->startsAt('next Tuesday 12:00')
            ->create();

        // When requesting slots with a 30-minute buffer
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&buffer=30");

        // Then the first available slot should be at 1:30pm, not 1pm
        $response->assertStatus(200)
            ->assertJsonFragment([
                'starts_at' => now()->next('Tuesday')->setTimeFromTimeString('13:30')->toDateTimeString(),
                'ends_at' => now()->next('Tuesday')->setTimeFromTimeString('14:30')->toDateTimeString(),
                'duration' => 60
            ]);
    }
} 