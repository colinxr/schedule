<?php

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConversationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $artist;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->artist = User::factory()->create(['role' => 'artist']);
    }

    public function test_can_submit_conversation_details()
    {
        $formData = [
            'artist_id' => $this->artist->id,
            'description' => 'A beautiful design concept',
            'email' => 'client@example.com',
            'reference_images' => [
                UploadedFile::fake()->image('design1.jpg'),
                UploadedFile::fake()->image('design2.jpg'),
            ],
        ];

        $response = $this->postJson('/api/conversations', $formData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'status',
                    'created_at',
                    'last_message_at',
                    'artist' => ['id', 'name', 'email'],
                    'details' => [
                        'description',
                        'reference_images',
                        'email',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('conversations', [
            'artist_id' => $this->artist->id,
            'status' => 'pending',
        ]);

        // Verify files were stored
        $conversation = Conversation::latest()->first();
        foreach ($conversation->details->reference_images as $image) {
            Storage::disk('public')->exists($image);
        }
    }

    public function test_validates_required_fields()
    {
        $response = $this->postJson('/api/conversations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'artist_id',
                'description',
                'email',
            ]);
    }

    public function test_validates_email_format()
    {
        $response = $this->postJson('/api/conversations', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
