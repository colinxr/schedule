<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->create();

        $this->assertTrue($profile->user->is($user));
    }

    public function test_user_can_have_profile(): void
    {
        $user = User::factory()
            ->has(UserProfile::factory(), 'profile')
            ->create();

        $this->assertInstanceOf(UserProfile::class, $user->profile);
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

        $profile = UserProfile::factory()
            ->withCustomSettings($settings)
            ->create();

        $this->assertIsArray($profile->settings);
        $this->assertEquals($settings, $profile->settings);
    }

    public function test_profile_can_have_null_settings(): void
    {
        $profile = UserProfile::factory()
            ->withoutSettings()
            ->create();

        $this->assertNull($profile->settings);
    }

    public function test_profile_is_deleted_when_user_is_deleted(): void
    {
        $user = User::factory()
            ->has(UserProfile::factory(), 'profile')
            ->create();

        $profileId = $user->profile->id;

        $user->delete();

        $this->assertDatabaseMissing('user_profiles', ['id' => $profileId]);
    }
} 