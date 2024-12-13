<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_artist_can_send_message_to_conversation()
    {
        // Create artist and client
        $artist = User::factory()->create(['role' => 'artist']);
        $client = User::factory()->create(['role' => 'client']);

        // Create conversation
        $conversation = Conversation::factory()->create([
            'artist_id' => $artist->id,
            'client_id' => $client->id,
        ]);

        // Act as artist
        $this->actingAs($artist);

        $response = $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'content' => 'Test message content'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'content',
                    'created_at',
                    'read_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'content' => 'Test message content'
                ]
            ]);

        // Assert message was created in database
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'content' => 'Test message content',
            'user_id' => $artist->id,
        ]);

        // Assert conversation last_message_at was updated
        $this->assertNotNull($conversation->fresh()->last_message_at);
    }

    public function test_non_artist_cannot_send_message()
    {
        $artist = User::factory()->create(['role' => 'artist']);
        $client = User::factory()->create(['role' => 'client']);
        $otherArtist = User::factory()->create(['role' => 'artist']);

        $conversation = Conversation::factory()->create([
            'artist_id' => $artist->id,
            'client_id' => $client->id,
        ]);

        // Act as another artist
        $this->actingAs($otherArtist);

        $response = $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'content' => 'Test message content'
        ]);

        $response->assertForbidden();

        // Assert no message was created
        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'content' => 'Test message content',
        ]);
    }

    public function test_message_content_is_required()
    {
        $artist = User::factory()->create(['role' => 'artist']);
        $client = User::factory()->create(['role' => 'client']);

        $conversation = Conversation::factory()->create([
            'artist_id' => $artist->id,
            'client_id' => $client->id,
        ]);

        $this->actingAs($artist);

        $response = $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'content' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }
} 