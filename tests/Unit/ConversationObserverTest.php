<?php

namespace Tests\Feature\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\ConversationDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConversationObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_initial_message_when_conversation_details_created(): void
    {
        // Create users
        $client = User::factory()->create();
        $artist = User::factory()->create();

        // Create conversation
        $conversation = Conversation::create([
            'client_id' => $client->id,
            'artist_id' => $artist->id,
            'status' => 'open',
        ]);

        // Create conversation details with description
        $description = 'This is a test description';
        ConversationDetails::create([
            'conversation_id' => $conversation->id,
            'description' => $description,
            'email' => 'test@example.com',
        ]);

        // Assert message was created
        $message = Message::where('conversation_id', $conversation->id)->first();
        
        $this->assertNotNull($message);
        $this->assertEquals($description, $message->content);
        $this->assertEquals($client->id, $message->sender_id);
        $this->assertEquals(User::class, $message->sender_type);
        $this->assertNotNull($conversation->fresh()->last_message_at);

        // Verify the polymorphic relationship works
        $this->assertTrue($message->sender->is($client));
    }

    public function test_does_not_create_message_when_description_is_empty(): void
    {
        // Create users
        $client = User::factory()->create();
        $artist = User::factory()->create();

        // Create conversation
        $conversation = Conversation::create([
            'client_id' => $client->id,
            'artist_id' => $artist->id,
            'status' => 'open',
        ]);

        // Create conversation details without description
        ConversationDetails::create([
            'conversation_id' => $conversation->id,
            'description' => '',
            'email' => 'test@example.com',
        ]);

        // Assert no message was created
        $this->assertEquals(0, Message::where('conversation_id', $conversation->id)->count());
        $this->assertNull($conversation->fresh()->last_message_at);
    }
}
