<?php

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_belongs_to_conversation(): void
    {
        $conversation = Conversation::factory()
            ->for(User::factory()->create(['role' => 'artist']), 'artist')
            ->for(User::factory()->create(['role' => 'client']), 'client')
            ->create();

        $message = Message::factory()
            ->for($conversation)
            ->create();

        $this->assertTrue($message->conversation->is($conversation));
    }

    public function test_message_belongs_to_sender(): void
    {
        $sender = User::factory()->create(['role' => 'artist']);
        $conversation = Conversation::factory()
            ->for($sender, 'artist')
            ->for(User::factory()->create(['role' => 'client']), 'client')
            ->create();

        $message = Message::factory()
            ->for($conversation)
            ->create([
                'sender_type' => User::class,
                'sender_id' => $sender->id
            ]);

        $this->assertTrue($message->sender->is($sender));
    }

    public function test_message_can_be_marked_as_read(): void
    {
        $conversation = Conversation::factory()
            ->for(User::factory()->create(['role' => 'artist']), 'artist')
            ->for(User::factory()->create(['role' => 'client']), 'client')
            ->create();

        $message = Message::factory()
            ->for($conversation)
            ->create(['read_at' => null]);

        $this->assertNull($message->read_at);

        $message->markAsRead();
        
        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_message_can_have_attachments(): void
    {
        $conversation = Conversation::factory()
            ->for(User::factory()->create(['role' => 'artist']), 'artist')
            ->for(User::factory()->create(['role' => 'client']), 'client')
            ->create();

        $message = Message::factory()
            ->for($conversation)
            ->create([
                'content' => 'Message with attachment'
            ]);

        $this->assertNotNull($message->content);
    }
} 