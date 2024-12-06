<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AppointmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $artist;
    private User $client;
    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artist = User::factory()->create(['role' => 'artist']);
        $this->client = User::factory()->create(['role' => 'client']);
        $this->conversation = Conversation::factory()
            ->for($this->artist, 'artist')
            ->for($this->client, 'client')
            ->create();
    }

    public function test_can_create_appointment(): void
    {
        $this->actingAs($this->artist);

        $response = $this->postJson('/api/appointments', [
            'conversation_id' => $this->conversation->id,
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(2)->toDateTimeString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'starts_at',
                    'ends_at',
                    'artist_id',
                    'client_id',
                    'conversation_id',
                ]
            ]);

        $this->assertDatabaseHas('appointments', [
            'conversation_id' => $this->conversation->id,
            'artist_id' => $this->artist->id,
            'client_id' => $this->client->id,
        ]);
    }

    public function test_can_update_appointment(): void
    {
        $this->actingAs($this->artist);

        $appointment = Appointment::factory()
            ->for($this->conversation)
            ->for($this->artist, 'artist')
            ->for($this->client, 'client')
            ->create();

        $newStartTime = now()->addDays(2);
        $newEndTime = $newStartTime->copy()->addHours(2);

        $response = $this->putJson("/api/appointments/{$appointment->id}", [
            'starts_at' => $newStartTime->toDateTimeString(),
            'ends_at' => $newEndTime->toDateTimeString(),
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'starts_at' => $newStartTime->toDateTimeString(),
            'ends_at' => $newEndTime->toDateTimeString(),
        ]);
    }

    public function test_can_delete_appointment(): void
    {
        $this->actingAs($this->artist);

        $appointment = Appointment::factory()
            ->for($this->conversation)
            ->for($this->artist, 'artist')
            ->for($this->client, 'client')
            ->create();

        $response = $this->deleteJson("/api/appointments/{$appointment->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    public function test_validates_required_fields(): void
    {
        $this->actingAs($this->artist);

        $response = $this->postJson('/api/appointments', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['conversation_id', 'starts_at', 'ends_at']);
    }

    public function test_validates_end_time_after_start_time(): void
    {
        $this->actingAs($this->artist);

        $startTime = now()->addDay();
        $endTime = $startTime->copy()->subHour(); // End time before start time

        $response = $this->postJson('/api/appointments', [
            'conversation_id' => $this->conversation->id,
            'starts_at' => $startTime->toDateTimeString(),
            'ends_at' => $endTime->toDateTimeString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ends_at']);
    }

    public function test_only_artist_can_manage_appointments(): void
    {
        $this->actingAs($this->client); // Acting as client instead of artist

        $response = $this->postJson('/api/appointments', [
            'conversation_id' => $this->conversation->id,
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->addHours(2)->toDateTimeString(),
        ]);

        $response->assertStatus(403);
    }
} 