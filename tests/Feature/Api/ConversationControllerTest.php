<?php

namespace Tests\Feature\Api;

use App\Events\ConversationCreated;
use App\Models\Conversation;
use App\Models\ConversationDetails;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
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

    public function test_can_submit_conversation_details_and_create_client()
    {
        Event::fake();

        $formData = [
            'artist_id' => $this->artist->id,
            'description' => 'A beautiful design concept',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'client@example.com',
            'phone' => '1234567890',
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
                    'artist' => ['id', 'first_name', 'last_name', 'email'],
                    'details' => [
                        'description',
                        'reference_images',
                        'email',
                    ],
                ],
            ]);

        // Assert conversation details were created
        $conversation = Conversation::latest()->first();
        $this->assertDatabaseHas('conversation_details', [
            'conversation_id' => $conversation->id,
            'description' => 'A beautiful design concept',
            'email' => 'client@example.com',
            'phone' => '1234567890',
        ]);

        // Verify files were stored
        foreach ($conversation->details->reference_images as $image) {
            Storage::disk('public')->exists($image);
        }

        // Assert event was dispatched
        Event::assertDispatched(ConversationCreated::class, function ($event) use ($conversation) {
            return $event->conversationId === $conversation->id
                && $event->clientData['email'] === 'client@example.com';
        });
    }

    public function test_can_submit_conversation_without_optional_fields()
    {
        Event::fake();

        $formData = [
            'artist_id' => $this->artist->id,
            'description' => 'A beautiful design concept',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'client@example.com',
        ];

        $response = $this->postJson('/api/conversations', $formData);

        $response->assertStatus(201);
        
        $conversation = Conversation::latest()->first();
        $this->assertNull($conversation->details->phone);
        $this->assertEmpty($conversation->details->reference_images);
    }

    public function test_fails_if_client_creation_fails()
    {
        $formData = [
            'artist_id' => $this->artist->id,
            'description' => 'A beautiful design concept',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',
            'reference_images' => [],
        ];

        $response = $this->postJson('/api/conversations', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Assert no conversation or user was created
        $this->assertDatabaseCount('conversations', 0);
        $this->assertDatabaseCount('users', 1); // Only the artist exists
    }

    public function test_fails_if_artist_does_not_exist()
    {
        $formData = [
            'artist_id' => 9999,
            'description' => 'A beautiful design concept',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'client@example.com',
        ];

        $response = $this->postJson('/api/conversations', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['artist_id']);
    }

    public function test_validates_required_fields()
    {
        $response = $this->postJson('/api/conversations', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'artist_id',
                'description',
                'first_name',
                'last_name',
                'email',
            ]);
    }

    public function test_validates_reference_images()
    {
        $formData = [
            'artist_id' => $this->artist->id,
            'description' => 'A beautiful design concept',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'client@example.com',
            'reference_images' => [
                UploadedFile::fake()->create('document.pdf', 100),
            ],
        ];

        $response = $this->postJson('/api/conversations', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reference_images.0']);
    }

    public function test_artist_can_view_own_conversation(): void
    {
        $artist = User::factory()->create(['role' => 'artist']);
        $conversation = Conversation::factory()->create(['artist_id' => $artist->id]);
        ConversationDetails::factory()->create([
            'conversation_id' => $conversation->id,
            'email' => 'client@example.com',
            'description' => 'Test description'
        ]);

        $this->actingAs($artist);
        $response = $this->getJson("/api/conversations/{$conversation->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'created_at',
                    'last_message_at',
                    'artist' => [
                        'id',
                        'first_name',
                        'last_name',
                        'email',
                    ],
                    'details' => [
                        'description',
                        'reference_images',
                        'email',
                    ],
                ],
            ]);
    }

    public function test_artist_cannot_view_others_conversation(): void
    {
        $artist = User::factory()->create(['role' => 'artist']);
        $otherArtist = User::factory()->create(['role' => 'artist']);
        $conversation = Conversation::factory()->create(['artist_id' => $artist->id]);
        ConversationDetails::factory()->create([
            'conversation_id' => $conversation->id,
            'email' => 'client@example.com',
            'description' => 'Test description'
        ]);

        $this->actingAs($otherArtist);
        $response = $this->getJson("/api/conversations/{$conversation->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_conversation(): void
    {
        $artist = User::factory()->create(['role' => 'artist']);
        $conversation = Conversation::factory()->create(['artist_id' => $artist->id]);
        ConversationDetails::factory()->create([
            'conversation_id' => $conversation->id,
            'email' => 'client@example.com',
            'description' => 'Test description'
        ]);

        $response = $this->getJson("/api/conversations/{$conversation->id}");

        $response->assertUnauthorized();
    }

    public function test_returns_404_for_nonexistent_conversation()
    {
        $this->actingAs($this->artist);
        $response = $this->getJson("/api/conversations/99999");
        $response->assertNotFound();
    }

    public function test_prevents_duplicate_email_for_clients()
    {
        // First conversation/client
        $this->postJson('/api/conversations', [
            'artist_id' => $this->artist->id,
            'description' => 'First design',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'client@example.com',
        ])->assertStatus(201);

        // Second conversation with same email
        $response = $this->postJson('/api/conversations', [
            'artist_id' => $this->artist->id,
            'description' => 'Second design',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'client@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_handles_large_reference_images()
    {
        $formData = [
            'artist_id' => $this->artist->id,
            'description' => 'A beautiful design concept',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'client@example.com',
            'reference_images' => [
                UploadedFile::fake()->image('large.jpg')->size(6000),
            ],
        ];

        $response = $this->postJson('/api/conversations', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reference_images.0']);
    }
}
