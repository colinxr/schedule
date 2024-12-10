<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Profile;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\ConversationDetails;
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
        $this->withoutExceptionHandling();
        
        // Create artist with email notifications enabled
        $artist = User::factory()
            ->has(Profile::factory()->state([
                'settings' => ['notifications' => ['email' => true]]
            ]), 'profile')
            ->create(['role' => 'artist']);

        $client = User::factory()->create(['role' => 'client']);

        // Fake notifications after creating users but before creating conversation
        Notification::fake();

        // Create conversation (ConversationDetails will be created automatically)
        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->withDetails()
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

    public function test_default_deposit_percentage_is_thirty_percent(): void
    {
        $profile = Profile::factory()->create();
        $this->assertEquals(30, $profile->getSetting('deposit_percentage', 30));
    }

    public function test_can_update_deposit_percentage(): void
    {
        $profile = Profile::factory()->create();
        
        $profile->updateSettings(['deposit_percentage' => 50]);
        
        $this->assertEquals(50, $profile->getSetting('deposit_percentage'));
    }

    public function test_deposit_percentage_can_be_zero(): void
    {
        $profile = Profile::factory()->create();
        
        $profile->updateSettings(['deposit_percentage' => 0]);
        
        $this->assertEquals(0, $profile->getSetting('deposit_percentage'));
    }

    public function test_deposit_percentage_cannot_exceed_hundred(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $profile = Profile::factory()->create();
        $profile->updateSettings(['deposit_percentage' => 101]);
    }

    public function test_deposit_percentage_cannot_be_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $profile = Profile::factory()->create();
        $profile->updateSettings(['deposit_percentage' => -1]);
    }
} 