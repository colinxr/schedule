<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\Profile;
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

    public function test_artist_can_update_appointment_price(): void
    {
        $this->actingAs($this->artist);

        // Create artist profile with default deposit percentage
        $profile = Profile::factory()->create([
            'user_id' => $this->artist->id,
            'settings' => ['deposit_percentage' => 30]
        ]);

        $appointment = Appointment::factory()
            ->for($this->conversation)
            ->for($this->artist, 'artist')
            ->for($this->client, 'client')
            ->create(['price' => null]);

        $response = $this->patchJson("/api/appointments/{$appointment->id}/price", [
            'price' => 150.00
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'price' => '150.00',
                    'deposit_amount' => '45.00', // 30% default deposit
                    'remaining_balance' => 150.00 // Full price until deposit is marked as paid
                ]
            ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'price' => 150.00,
            'deposit_amount' => 45.00
        ]);
    }

    public function test_artist_can_update_appointment_deposit(): void
    {
        $this->actingAs($this->artist);

        $appointment = Appointment::factory()
            ->for($this->conversation)
            ->for($this->artist, 'artist')
            ->for($this->client, 'client')
            ->create([
                'price' => 200.00,
                'deposit_amount' => 60.00
            ]);

        $response = $this->patchJson("/api/appointments/{$appointment->id}/deposit", [
            'deposit_amount' => 80.00
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'price' => '200.00',
                    'deposit_amount' => '80.00',
                    'remaining_balance' => 200.00 // Full price until deposit is marked as paid
                ]
            ]);
    }

    public function test_deposit_cannot_exceed_price(): void
    {
        $this->actingAs($this->artist);

        $appointment = Appointment::factory()
            ->for($this->conversation)
            ->for($this->artist, 'artist')
            ->for($this->client, 'client')
            ->create([
                'price' => 100.00,
                'deposit_amount' => 30.00
            ]);

        $response = $this->patchJson("/api/appointments/{$appointment->id}/deposit", [
            'deposit_amount' => 150.00
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['deposit_amount']);
    }

    public function test_cannot_set_deposit_without_price(): void
    {
        $this->actingAs($this->artist);

        $appointment = Appointment::factory()
            ->for($this->conversation)
            ->for($this->artist, 'artist')
            ->for($this->client, 'client')
            ->create(['price' => null]);

        $response = $this->patchJson("/api/appointments/{$appointment->id}/deposit", [
            'deposit_amount' => 50.00
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot set deposit amount without a price.'
            ]);
    }

    public function test_client_cannot_update_appointment_price(): void
    {
        $this->actingAs($this->client);

        $appointment = Appointment::factory()
            ->for($this->conversation)
            ->for($this->artist, 'artist')
            ->for($this->client, 'client')
            ->create();

        $response = $this->patchJson("/api/appointments/{$appointment->id}/price", [
            'price' => 150.00
        ]);

        $response->assertForbidden();
    }

    public function test_client_cannot_update_appointment_deposit(): void
    {
        $this->actingAs($this->client);

        $appointment = Appointment::factory()
            ->for($this->conversation)
            ->for($this->artist, 'artist')
            ->for($this->client, 'client')
            ->create();

        $response = $this->patchJson("/api/appointments/{$appointment->id}/deposit", [
            'deposit_amount' => 50.00
        ]);

        $response->assertForbidden();
    }

    public function test_artist_can_mark_deposit_as_paid(): void
    {
        $this->actingAs($this->artist);

        $appointment = Appointment::factory()
            ->for($this->conversation)
            ->for($this->artist, 'artist')
            ->for($this->client, 'client')
            ->create([
                'price' => 200.00,
                'deposit_amount' => 60.00,
                'deposit_paid_at' => null
            ]);

        $response = $this->patchJson("/api/appointments/{$appointment->id}/deposit/toggle-paid");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'is_deposit_paid' => true,
                    'deposit_amount' => '60.00',
                    'remaining_balance' => 140.00
                ]
            ]);

        $this->assertNotNull($appointment->fresh()->deposit_paid_at);
    }

    public function test_artist_can_mark_deposit_as_unpaid(): void
    {
        $this->actingAs($this->artist);

        $appointment = Appointment::factory()
            ->for($this->conversation)
            ->for($this->artist, 'artist')
            ->for($this->client, 'client')
            ->create([
                'price' => 200.00,
                'deposit_amount' => 60.00,
                'deposit_paid_at' => now()
            ]);

        $response = $this->patchJson("/api/appointments/{$appointment->id}/deposit/toggle-paid");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'is_deposit_paid' => false,
                    'deposit_paid_at' => null,
                    'deposit_amount' => '60.00',
                    'remaining_balance' => 200.00
                ]
            ]);

        $this->assertNull($appointment->fresh()->deposit_paid_at);
    }

    public function test_cannot_mark_deposit_paid_without_deposit_amount(): void
    {
        $this->actingAs($this->artist);

        $appointment = Appointment::factory()
            ->for($this->conversation)
            ->for($this->artist, 'artist')
            ->for($this->client, 'client')
            ->create([
                'price' => 200.00,
                'deposit_amount' => null,
                'deposit_paid_at' => null
            ]);

        $response = $this->patchJson("/api/appointments/{$appointment->id}/deposit/toggle-paid");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot mark deposit as paid when no deposit amount is set.'
            ]);
    }

    public function test_client_cannot_mark_deposit_as_paid(): void
    {
        $this->actingAs($this->client);

        $appointment = Appointment::factory()
            ->for($this->conversation)
            ->for($this->artist, 'artist')
            ->for($this->client, 'client')
            ->create([
                'price' => 200.00,
                'deposit_amount' => 60.00
            ]);

        $response = $this->patchJson("/api/appointments/{$appointment->id}/deposit/toggle-paid");

        $response->assertForbidden();
    }
} 