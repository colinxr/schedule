<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $artist;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base users for tests
        $this->artist = User::factory()->create(['role' => 'artist']);
        $this->client = User::factory()->create(['role' => 'client']);
    }

    public function test_artist_can_view_client_details(): void
    {
        $this->withoutExceptionHandling();
        // Create the artist-client relationship
        $this->artist->clients()->attach($this->client);

        
        // Act as the artist and make the request
        $response = $this->actingAs($this->artist)
            ->getJson("/api/client/{$this->client->id}");

        // Assert successful response
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'conversations',
                    'appointments',
                ]
            ]);
    }

    public function test_artist_cannot_view_unrelated_client_details(): void
    {
        $unrelatedClient = User::factory()->create(['role' => 'client']);

        // Act as the artist and try to access unrelated client
        $response = $this->actingAs($this->artist)
            ->getJson("/api/client/{$unrelatedClient->id}");

        // Assert forbidden response
        $response->assertForbidden()
            ->assertJson([
                'message' => 'You do not have access to this client\'s information.'
            ]);
    }

    public function test_client_cannot_access_client_details_endpoint(): void
    {
        $client2 = User::factory()->create(['role' => 'client']);

        // Act as client1 and try to access client2's details
        $response = $this->actingAs($this->client)
            ->getJson("/api/client/{$client2->id}");

        // Assert forbidden response due to role middleware
        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_client_details(): void
    {
        // Make request without authentication
        $response = $this->getJson("/api/client/{$this->client->id}");

        // Assert unauthorized response
        $response->assertUnauthorized();
    }

    public function test_artist_can_view_client_with_appointments_and_conversations(): void
    {
        // Create the artist-client relationship
        $this->artist->clients()->attach($this->client);

        // Create some appointments and conversations
        $appointment = Appointment::factory()->create([
            'artist_id' => $this->artist->id,
            'client_id' => $this->client->id,
        ]);

        $conversation = Conversation::factory()->create([
            'artist_id' => $this->artist->id,
            'client_id' => $this->client->id,
        ]);

        // Act as the artist and make the request
        $response = $this->actingAs($this->artist)
            ->getJson("/api/client/{$this->client->id}");

        // Assert successful response with relationships
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $this->client->id,
                    'appointments' => [
                        ['id' => $appointment->id]
                    ],
                    'conversations' => [
                        ['id' => $conversation->id]
                    ]
                ]
            ]);
    }
}
