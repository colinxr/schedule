<?php

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\ConversationDetails;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_details_belongs_to_conversation(): void
    {
        $conversation = Conversation::factory()
            ->for(User::factory()->create(['role' => 'artist']), 'artist')
            ->for(User::factory()->create(['role' => 'client']), 'client')
            ->create();

        $details = ConversationDetails::factory()
            ->for($conversation)
            ->create();

        $this->assertTrue($details->conversation->is($conversation));
    }

    public function test_conversation_details_has_required_fields(): void
    {
        $conversation = Conversation::factory()
            ->for(User::factory()->create(['role' => 'artist']), 'artist')
            ->for(User::factory()->create(['role' => 'client']), 'client')
            ->create();

        $details = ConversationDetails::factory()
            ->for($conversation)
            ->create([
                'email' => 'test@example.com',
                'phone' => '1234567890'
            ]);

        $this->assertEquals('test@example.com', $details->email);
        $this->assertEquals('1234567890', $details->phone);
    }

    public function test_conversation_details_can_have_optional_fields(): void
    {
        $conversation = Conversation::factory()
            ->for(User::factory()->create(['role' => 'artist']), 'artist')
            ->for(User::factory()->create(['role' => 'client']), 'client')
            ->create();

        $details = ConversationDetails::factory()
            ->for($conversation)
            ->create([
                'instagram' => '@test'
            ]);

        $this->assertEquals('@test', $details->instagram);
    }

    public function test_conversation_details_reference_images_is_cast_to_array(): void
    {
        $conversation = Conversation::factory()
            ->for(User::factory()->create(['role' => 'artist']), 'artist')
            ->for(User::factory()->create(['role' => 'client']), 'client')
            ->create();

        $details = ConversationDetails::factory()
            ->for($conversation)
            ->create([
                'reference_images' => ['image1.jpg', 'image2.jpg']
            ]);

        $this->assertIsArray($details->reference_images);
        $this->assertCount(2, $details->reference_images);
    }
}
