<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Services\GoogleCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;

class AppointmentListingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock GoogleCalendarService
        $this->mock(GoogleCalendarService::class, function ($mock) {
            $mock->shouldReceive('createEvent')->andReturn('event_id');
            $mock->shouldReceive('updateEvent')->andReturn(true);
            $mock->shouldReceive('deleteEvent')->andReturn(true);
        });
    }

    public function test_artist_can_view_their_appointments()
    {
        $artist = User::factory()->create(['role' => 'artist']);
        Sanctum::actingAs($artist);

        Appointment::factory()
            ->count(3)
            ->for($artist, 'artist')
            ->create();

        $response = $this->getJson('/api/appointments');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'starts_at',
                        'ends_at',
                        'artist' => ['id', 'name'],
                        'client' => ['id', 'name'],
                    ]
                ]
            ]);
    }

    public function test_client_can_view_their_appointments()
    {
        $client = User::factory()->create(['role' => 'client']);
        Sanctum::actingAs($client);

        Appointment::factory()
            ->count(2)
            ->for($client, 'client')
            ->create();

        $response = $this->getJson('/api/appointments');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'starts_at',
                        'ends_at',
                        'artist' => ['id', 'name'],
                        'client' => ['id', 'name'],
                    ]
                ]
            ]);
    }

    public function test_artist_can_view_specific_appointment()
    {
        $artist = User::factory()->create(['role' => 'artist']);
        Sanctum::actingAs($artist);

        $appointment = Appointment::factory()
            ->for($artist, 'artist')
            ->create();

        $response = $this->getJson("/api/appointments/{$appointment->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'starts_at',
                    'ends_at',
                    'artist' => ['id', 'name'],
                    'client' => ['id', 'name'],
                ]
            ]);
    }

    public function test_client_can_view_specific_appointment()
    {
        $client = User::factory()->create(['role' => 'client']);
        Sanctum::actingAs($client);

        $appointment = Appointment::factory()
            ->for($client, 'client')
            ->create();

        $response = $this->getJson("/api/appointments/{$appointment->id}");

        $response->assertOk();
    }

    public function test_user_cannot_view_others_appointment()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $appointment = Appointment::factory()->create();

        $response = $this->getJson("/api/appointments/{$appointment->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_appointments()
    {
        $response = $this->getJson('/api/appointments');

        $response->assertUnauthorized();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
