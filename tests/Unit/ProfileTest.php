<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Profile;
use App\Models\Message;
use App\Models\Conversation;
use App\Notifications\NewMessageNotification;
use App\Notifications\NewConversationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->for($user)->create();

        $this->assertTrue($profile->user->is($user));
    }

    public function test_user_can_have_profile(): void
    {
        $user = User::factory()
            ->has(Profile::factory(), 'profile')
            ->create();

        $this->assertInstanceOf(Profile::class, $user->profile);
    }

    public function test_profile_settings_are_cast_to_array(): void
    {
        $settings = [
            'notifications' => [
                'email' => false,
                'push' => true,
            ],
            'theme' => 'dark',
        ];

        $profile = Profile::factory()
            ->withCustomSettings($settings)
            ->create();

        $this->assertIsArray($profile->settings);
        $this->assertEquals($settings, $profile->settings);
    }

    public function test_profile_can_have_null_settings(): void
    {
        $profile = Profile::factory()
            ->withoutSettings()
            ->create();

        $this->assertNull($profile->settings);
    }

    public function test_profile_is_deleted_when_user_is_deleted(): void
    {
        $user = User::factory()
            ->has(Profile::factory(), 'profile')
            ->create();

        $profileId = $user->profile->id;

        $user->delete();

        $this->assertDatabaseMissing('profiles', ['id' => $profileId]);
    }

    public function test_user_can_update_email_notification_settings(): void
    {
        $user = User::factory()
            ->has(Profile::factory()->state([
                'settings' => ['notifications' => ['email' => true]]
            ]), 'profile')
            ->create();

        $user->profile->updateSettings(['notifications.email' => false]);

        $this->assertFalse($user->profile->fresh()->settings['notifications']['email']);
    }

    public function test_email_notifications_are_not_sent_when_disabled(): void
    {
        Notification::fake();

        // Create artist with email notifications disabled
        $artist = User::factory()
            ->has(Profile::factory()->state([
                'settings' => ['notifications' => ['email' => false]]
            ]), 'profile')
            ->create(['role' => 'artist']);

        $client = User::factory()->create(['role' => 'client']);

        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->create();

        // Create a message from client
        Message::create([
            'conversation_id' => $conversation->id,
            'content' => 'Test message',
            'sender_type' => User::class,
            'sender_id' => $client->id,
        ]);

        // Assert notification was not sent via email
        Notification::assertNotSentTo(
            $artist,
            NewMessageNotification::class,
            function ($notification, $channels) {
                return in_array('mail', $channels);
            }
        );
    }

    public function test_email_notifications_are_sent_when_enabled(): void
    {
        Notification::fake();

        // Create artist with email notifications enabled
        $artist = User::factory()
            ->has(Profile::factory()->state([
                'settings' => ['notifications' => ['email' => true]]
            ]), 'profile')
            ->create(['role' => 'artist']);

        $client = User::factory()->create(['role' => 'client']);

        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->create();

        // Assert notification was sent via email
        Notification::assertSentTo(
            $artist,
            NewConversationNotification::class,
            function ($notification, $channels) {
                return in_array('mail', $channels);
            }
        );
    }
} 