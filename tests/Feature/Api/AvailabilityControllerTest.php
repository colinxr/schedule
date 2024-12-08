<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\WorkSchedule;
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

        // Create an artist
        $this->artist = User::factory()->create(['role' => 'artist']);

        // Create work schedules for weekdays
        for ($day = 1; $day <= 6; $day++) {
            WorkSchedule::create([
                'user_id' => $this->artist->id,
                'day_of_week' => $day,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_active' => true
            ]);
        }

        // Set test time to 9 AM
        Carbon::setTestNow(Carbon::today()->setHour(9));
    }

    public function test_returns_paginated_available_slots()
    {
        $this->actingAs($this->artist);
        
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&per_page=2");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'available_slots',
                'pagination' => [
                    'total',
                    'per_page',
                    'current_page',
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
        
        // First get total pages
        $response = $this->getJson("/api/artists/{$this->artist->id}/available-slots?duration=60&per_page=10");
        $totalPages = $response->json('pagination.total_pages');
        
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

    public function test_caches_results_with_same_parameters()
    {
        $this->actingAs($this->artist);
        
        $url = "/api/artists/{$this->artist->id}/available-slots?duration=60";
        
        // First request
        $response1 = $this->getJson($url);
        $etag1 = $response1->headers->get('ETag');
        
        // Second request with same parameters
        $response2 = $this->getJson($url);
        $etag2 = $response2->headers->get('ETag');
        
        $this->assertEquals($etag1, $etag2);
    }
} 