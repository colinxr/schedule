<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\ConversationDetails;
use App\Notifications\NewConversationNotification;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class ConversationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_artist_receives_notification_for_new_conversation(): void
    {
        Notification::fake();

        $artist = User::factory()->create(['role' => 'artist']);
        $client = User::factory()->create(['role' => 'client']);

        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->create();

        ConversationDetails::factory()->create([
            'conversation_id' => $conversation->id,
            'description' => 'Test description',
            'email' => 'test@example.com'
        ]);

        Notification::assertSentTo(
            $artist,
            NewConversationNotification::class,
            function ($notification) use ($conversation) {
                return $notification->conversation->id === $conversation->id;
            }
        );
    }

    public function test_artist_does_not_receive_message_notification_for_initial_message(): void
    {
        Notification::fake();

        $artist = User::factory()->create(['role' => 'artist']);
        $client = User::factory()->create(['role' => 'client']);

        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->create();

        ConversationDetails::factory()->create([
            'conversation_id' => $conversation->id,
            'description' => 'Test description',
            'email' => 'test@example.com'
        ]);

        // Verify only the conversation notification was sent, not a message notification
        Notification::assertSentTo(
            $artist,
            NewConversationNotification::class
        );

        Notification::assertNotSentTo(
            $artist,
            NewMessageNotification::class
        );
    }

    public function test_notification_contains_correct_conversation_data(): void
    {
        Notification::fake();

        $artist = User::factory()->create(['role' => 'artist']);
        $client = User::factory()->create(['role' => 'client']);
        $description = 'Test conversation description';

        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->create();

        ConversationDetails::factory()->create([
            'conversation_id' => $conversation->id,
            'description' => $description,
            'email' => 'test@example.com'
        ]);

        Notification::assertSentTo(
            $artist,
            NewConversationNotification::class,
            function ($notification, $channels) use ($conversation, $client, $description, $artist) {
                $data = $notification->toArray($artist);
                return $data['conversation_id'] === $conversation->id &&
                    $data['client_id'] === $client->id &&
                    $data['client_name'] === $client->name &&
                    $data['description'] === $description;
            }
        );
    }

    public function test_client_never_receives_notifications(): void
    {
        Notification::fake();

        $artist = User::factory()->create(['role' => 'artist']);
        $client = User::factory()->create(['role' => 'client']);

        // Create conversation (triggers NewConversationNotification)
        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->create();

        ConversationDetails::factory()->create([
            'conversation_id' => $conversation->id,
            'description' => 'Test description',
            'email' => 'test@example.com'
        ]);

        // Create a message from artist (should not notify client)
        Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Message from artist',
            'user_id' => $artist->id,
        ]);

        // Create another message from client (should not notify client)
        Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Message from client',
            'user_id' => $client->id,
        ]);

        // Verify client never receives any type of notification
        Notification::assertNothingSentTo($client);
    }
} 