<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\WorkSchedule;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityControllerTest extends TestCase
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

    public function test_returns_paginated_available_slots()
    {
        $this->actingAs($this->artist);
        
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&per_page=2");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'available_slots',
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'total_pages',
                    'has_more_pages',
                    'from',
                    'to'
                ]
            ])
            ->assertJsonCount(2, 'available_slots');

        $this->assertEquals(2, $response->json('pagination.per_page'));
        $this->assertTrue($response->json('pagination.total') > 2);
    }

    public function test_respects_custom_page_size()
    {
        $this->actingAs($this->artist);
        
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&per_page=5");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'available_slots')
            ->assertJson([
                'pagination' => [
                    'per_page' => 5
                ]
            ]);
    }

    public function test_handles_last_page()
    {
        $this->actingAs($this->artist);
        
        // First get total number of slots
        $firstResponse = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&per_page=10");
        $totalPages = $firstResponse->json('pagination.total_pages');
        
        // Request the last page
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&per_page=10&page={$totalPages}");

        $response->assertStatus(200)
            ->assertJson([
                'pagination' => [
                    'current_page' => $totalPages,
                    'has_more_pages' => false
                ]
            ]);
    }

    public function test_validates_pagination_parameters()
    {
        $this->actingAs($this->artist);
        
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&per_page=0&page=0");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page', 'page']);
    }

    public function test_enforces_maximum_per_page_limit()
    {
        $this->actingAs($this->artist);
        
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&per_page=100");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page'])
            ->assertJson([
                'errors' => [
                    'per_page' => ['Cannot request more than 50 slots per page']
                ]
            ]);
    }

    public function test_returns_empty_slots_with_pagination_metadata()
    {
        $this->actingAs($this->artist);
        
        // Request slots for Sunday (no work schedule)
        $nextSunday = now()->next('Sunday')->format('Y-m-d');
        
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&date={$nextSunday}");

        $response->assertStatus(200)
            ->assertJson([
                'available_slots' => [],
                'pagination' => [
                    'total' => 0,
                    'total_pages' => 0,
                    'has_more_pages' => false
                ]
            ]);
    }

    public function test_caches_results_with_same_parameters()
    {
        $this->actingAs($this->artist);
        
        // Make first request
        $firstResponse = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&per_page=5");
        
        // Create an appointment that should affect availability
        Appointment::factory()
            ->for($this->artist, 'artist')
            ->duration(60)
            ->startsAt('next Tuesday 11:00')
            ->create();

        // Make second request with same parameters
        $secondResponse = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&per_page=5");

        // Responses should be identical due to caching
        $this->assertEquals(
            $firstResponse->json('available_slots'),
            $secondResponse->json('available_slots')
        );
    }
} 