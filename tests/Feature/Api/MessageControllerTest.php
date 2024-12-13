<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user and conversation for testing
        $this->user = User::factory()->create();
        $this->conversation = Conversation::factory()->create([
            'artist_id' => $this->user->id,
        ]);
    }

    public function test_can_create_message()
    {
        $payload = [
            'content' => 'Test message content',
            'conversation_id' => $this->conversation->id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/messages', $payload);

        $response->assertCreated()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'content',
                    'created_at',
                    'read_at',
                ]
            ]);

        $this->assertDatabaseHas('messages', [
            'content' => 'Test message content',
            'conversation_id' => $this->conversation->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_cannot_create_message_in_others_conversation()
    {
        $otherUser = User::factory()->create();
        $othersConversation = Conversation::factory()->create([
            'artist_id' => $otherUser->id,
        ]);

        $payload = [
            'content' => 'Test message content',
            'conversation_id' => $othersConversation->id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/messages', $payload);

        $response->assertForbidden();

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $othersConversation->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_cannot_create_message_when_unauthenticated()
    {
        $payload = [
            'content' => 'Test message content',
            'conversation_id' => $this->conversation->id,
        ];

        $response = $this->postJson('/api/messages', $payload);

        $response->assertUnauthorized();
    }

    public function test_cannot_create_message_with_invalid_data()
    {
        $payload = [
            'content' => '',  // Empty content
            'conversation_id' => 999999, // Non-existent conversation
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/messages', $payload);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content', 'conversation_id']);
    }
} 