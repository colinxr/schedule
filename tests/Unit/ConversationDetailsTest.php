<?php

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\ConversationDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_details_belongs_to_conversation()
    {
        $details = ConversationDetails::factory()->create();
        
        $this->assertInstanceOf(Conversation::class, $details->conversation);
    }

    public function test_conversation_details_has_required_fields()
    {
        $details = ConversationDetails::factory()->create([
            'description' => 'A beautiful design',
            'email' => 'test@example.com',
        ]);

        $this->assertNotNull($details->description);
        $this->assertNotNull($details->email);
    }

    public function test_conversation_details_can_have_optional_fields()
    {
        $details = ConversationDetails::factory()->create([
            'reference_images' => ['test1.jpg', 'test2.jpg'],
        ]);

        $this->assertIsArray($details->reference_images);
    }

    public function test_conversation_details_reference_images_is_cast_to_array()
    {
        $details = ConversationDetails::factory()->create([
            'reference_images' => ['test1.jpg', 'test2.jpg'],
        ]);

        $this->assertIsArray($details->reference_images);
        $this->assertCount(2, $details->reference_images);
    }
}
