<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AppointmentListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_artist_can_view_their_appointments()
    {
        $artist = User::factory()->create(['role' => 'artist']);
        Sanctum::actingAs($artist);

        // Create appointments for this artist
        $appointments = Appointment::factory()
            ->count(3)
            ->for($artist, 'artist')
            ->create();

        // Create appointments for another artist (should not be visible)
        Appointment::factory()
            ->count(2)
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
                        'client' => [
                            'id',
                            'name',
                            'email'
                        ]
                    ]
                ]
            ]);
    }

    public function test_client_can_view_their_appointments()
    {
        $client = User::factory()->create(['role' => 'client']);
        Sanctum::actingAs($client);

        // Create appointments for this client
        $appointments = Appointment::factory()
            ->count(2)
            ->for($client, 'client')
            ->create();

        // Create appointments for another client (should not be visible)
        Appointment::factory()
            ->count(3)
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
                        'artist' => [
                            'id',
                            'name',
                            'email'
                        ]
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
                    'artist',
                    'client',
                    'conversation' => [
                        'details'
                    ]
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
        $user = User::factory()->create(['role' => 'client']);
        Sanctum::actingAs($user);

        $appointment = Appointment::factory()->create();

        $response = $this->getJson("/api/appointments/{$appointment->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_appointments()
    {
        $response = $this->getJson('/api/appointments');
        $response->assertUnauthorized();

        $appointment = Appointment::factory()->create();
        $response = $this->getJson("/api/appointments/{$appointment->id}");
        $response->assertUnauthorized();
    }
}
