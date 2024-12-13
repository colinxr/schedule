<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Profile;
use App\Models\ConversationDetails;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class MessageNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_artist_receives_notification_when_client_sends_message(): void
    {
        Notification::fake();

        $artist = User::factory()
            ->has(Profile::factory()->state([
                'settings' => ['notifications' => ['email' => true]]
            ]))
            ->create(['role' => 'artist']);
            
        $client = User::factory()->create(['role' => 'client']);

        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->create();

        // Create initial conversation details
        ConversationDetails::create([
            'conversation_id' => $conversation->id,
            'description' => 'Initial message',
            'email' => 'test@example.com',
        ]);

        // Clear any notifications from conversation creation
        Notification::fake();

        // Create a second message from client (should trigger notification)
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Test message',
            'user_id' => $client->id,
        ]);

        // Load the conversation relationship with artist
        $message->load(['conversation.artist.profile']);

        Notification::assertSentTo(
            $artist,
            NewMessageNotification::class,
            function ($notification) use ($message) {
                return $notification->message->id === $message->id;
            }
        );
    }

    public function test_artist_does_not_receive_notification_for_own_message(): void
    {
        Notification::fake();

        $artist = User::factory()
            ->has(Profile::factory()->state([
                'settings' => ['notifications' => ['email' => true]]
            ]))
            ->create(['role' => 'artist']);
            
        $client = User::factory()->create(['role' => 'client']);

        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->create();

        // Create initial conversation details
        ConversationDetails::create([
            'conversation_id' => $conversation->id,
            'description' => 'Initial message',
            'email' => 'test@example.com',
        ]);

        // Clear any notifications from conversation creation
        Notification::fake();

        // Create a message from artist (should not trigger notification)
        Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Test message',
            'user_id' => $artist->id,
        ]);

        Notification::assertNotSentTo($artist, NewMessageNotification::class);
    }

    public function test_notification_contains_correct_data(): void
    {
        Notification::fake();

        $artist = User::factory()
            ->has(Profile::factory()->state([
                'settings' => ['notifications' => ['email' => true]]
            ]))
            ->create(['role' => 'artist']);
            
        $client = User::factory()->create(['role' => 'client']);

        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->create();

        // Create initial conversation details
        ConversationDetails::create([
            'conversation_id' => $conversation->id,
            'description' => 'Initial message',
            'email' => 'test@example.com',
        ]);

        // Clear any notifications from conversation creation
        Notification::fake();

        // Create a second message from client (should trigger notification)
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Test message content',
            'user_id' => $client->id,
        ]);

        // Load the conversation relationship with artist
        $message->load(['conversation.artist.profile']);

        Notification::assertSentTo(
            $artist,
            NewMessageNotification::class,
            function ($notification, $channels) use ($message, $conversation, $client, $artist) {
                $data = $notification->toArray($artist);
                return $data['message_id'] === $message->id &&
                    $data['conversation_id'] === $conversation->id &&
                    $data['user_id'] === $client->id &&
                    $data['content'] === 'Test message content';
            }
        );
    }
} 