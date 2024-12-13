<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\ConversationDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class ConversationDetailsObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure we're using the test database
        $this->assertTrue(
            app()->environment('testing'),
            'Tests should be run in testing environment'
        );

        // Run migrations if messages table doesn't exist
        if (!Schema::hasTable('messages')) {
            Artisan::call('migrate:fresh');
        }
    }

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
        $this->assertEquals($client->id, $message->user_id);
        $this->assertNotNull($conversation->fresh()->last_message_at);

        // Verify the polymorphic relationship works
        $this->assertTrue($message->user->is($client));
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