<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
} 