<?php

namespace Tests\Feature;

use App\Events\ConversationCreated;
use App\Models\Conversation;
use App\Models\ConversationDetails;
use App\Models\User;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

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

    public function test_can_submit_conversation_details_and_create_client(): void
    {
        Storage::fake('public');

        /** @var \App\Models\User $artist */
        $artist = User::factory()->create(['role' => 'artist']);

        $formData = [
            'artist_id' => $artist->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'client@example.com',
            'phone' => '1234567890',
            'description' => 'Test description',
            'reference_images' => [
                UploadedFile::fake()->image('test1.jpg'),
                UploadedFile::fake()->image('test2.jpg'),
            ],
        ];

        $response = $this->postJson('/api/conversations', $formData);
        
        if ($response->status() === 500) {
            dd($response->json());
        }

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'status',
                    'created_at',
                    'client' => [
                        'id',
                        'name',
                        'details' => [
                            'phone',
                            'email',
                            'instagram'
                        ]
                    ]
                ]
            ]);
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
            'reference_images' => ['not-an-image'],
        ];

        $response = $this->postJson('/api/conversations', $formData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reference_images.0']);
    }

    public function test_artist_can_view_own_conversation(): void
    {
        /** @var \App\Models\User $artist */
        $artist = User::factory()->create(['role' => 'artist']);
        
        /** @var \App\Models\User $client */
        $client = User::factory()->create(['role' => 'client']);
        
        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->has(ConversationDetails::factory())
            ->create();

        $this->actingAs($artist);
        $response = $this->getJson("/api/conversations/{$conversation->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'created_at',
                    'client' => [
                        'id',
                        'name',
                        'details' => [
                            'phone',
                            'email',
                            'instagram'
                        ]
                    ]
                ]
            ]);
    }

    public function test_artist_cannot_view_others_conversation(): void
    {
        /** @var \App\Models\User $artist */
        $artist = User::factory()->create(['role' => 'artist']);
        
        /** @var \App\Models\User $otherArtist */
        $otherArtist = User::factory()->create(['role' => 'artist']);
        
        /** @var \App\Models\User $client */
        $client = User::factory()->create(['role' => 'client']);
        
        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->create();

        $this->actingAs($otherArtist);
        $response = $this->getJson("/api/conversations/{$conversation->id}");

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_view_conversation(): void
    {
        /** @var \App\Models\User $artist */
        $artist = User::factory()->create(['role' => 'artist']);
        
        /** @var \App\Models\User $client */
        $client = User::factory()->create(['role' => 'client']);
        
        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->create();

        $response = $this->getJson("/api/conversations/{$conversation->id}");

        $response->assertUnauthorized();
    }

    public function test_returns_404_for_nonexistent_conversation()
    {
        $this->actingAs($this->artist);
        $response = $this->getJson("/api/conversations/99999");
        $response->assertNotFound();
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

    public function test_client_can_submit_multiple_requests(): void
    {
        Event::fake();
        
        $artist1 = User::factory()->create(['role' => 'artist']);
        $artist2 = User::factory()->create(['role' => 'artist']);
        
        // First request to artist1
        $response1 = $this->postJson('/api/conversations', [
            'artist_id' => $artist1->id,
            'description' => 'First design',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'client@example.com',
            'phone' => '1234567890'
        ]);
        
        $response1->assertStatus(201);
        
        // Second request to same artist
        $response2 = $this->postJson('/api/conversations', [
            'artist_id' => $artist1->id,
            'description' => 'Second design',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'client@example.com',
            'phone' => '1234567890'
        ]);
        
        $response2->assertStatus(201);
        
        // Request to different artist
        $response3 = $this->postJson('/api/conversations', [
            'artist_id' => $artist2->id,
            'description' => 'Third design',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'client@example.com',
            'phone' => '1234567890'
        ]);
        
        $response3->assertStatus(201);
        
        // Verify all conversations were created
        $this->assertDatabaseCount('conversations', 3);
        $this->assertDatabaseHas('conversation_details', [
            'description' => 'First design',
            'email' => 'client@example.com'
        ]);
        $this->assertDatabaseHas('conversation_details', [
            'description' => 'Second design',
            'email' => 'client@example.com'
        ]);
        $this->assertDatabaseHas('conversation_details', [
            'description' => 'Third design',
            'email' => 'client@example.com'
        ]);
        
        // Verify events were dispatched
        Event::assertDispatched(ConversationCreated::class, 3);
    }

    public function test_artist_can_list_their_conversations(): void
    {
        /** @var \App\Models\User $artist */
        $artist = User::factory()->create(['role' => 'artist']);
        
        /** @var \App\Models\User $client */
        $client = User::factory()->create(['role' => 'client']);
        
        // Create conversations for this artist with details and messages
        Conversation::factory()
            ->for($artist, 'artist')
            ->for($client, 'client')
            ->has(ConversationDetails::factory())
            ->count(3)
            ->create();
            
        // Create a conversation for another artist
        Conversation::factory()
            ->for(User::factory()->create(['role' => 'artist']), 'artist')
            ->for(User::factory()->create(['role' => 'client']), 'client')
            ->has(ConversationDetails::factory())
            ->create();

        $this->actingAs($artist);
        $response = $this->getJson('/api/conversations');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'status',
                    'created_at',
                    'client' => [
                        'id',
                        'name',
                        'details' => [
                            'phone',
                            'email',
                            'instagram'
                        ]
                    ]
                ]]
            ]);
    }

    public function test_artist_can_fetch_conversation_with_paginated_messages(): void
    {
        /** @var \App\Models\User $artist */
        $artist = User::factory()->create(['role' => 'artist']);
        
        /** @var \App\Models\User $client */
        $client = User::factory()->create(['role' => 'client']);
        
        $conversation = Conversation::factory()
            ->has(Message::factory()->count(75)) // Create 75 messages
            ->has(ConversationDetails::factory()->state([
                'phone' => '1234567890',
                'email' => 'client@example.com',
                'instagram' => '@client'
            ]))
            ->create([
                'artist_id' => $artist->id,
                'client_id' => $client->id
            ]);

        // Get total message count (75 + 1 initial message)
        $totalMessages = $conversation->messages()->count();
        $this->assertEquals(76, $totalMessages, 'Should have 76 messages (75 factory + 1 initial)');

        $this->actingAs($artist);
        
        // First page should return latest 50 messages
        $response = $this->getJson("/api/conversations/{$conversation->id}");
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'status',
                    'created_at',
                    'client' => [
                        'id',
                        'name',
                        'details' => [
                            'phone',
                            'email',
                            'instagram'
                        ]
                    ],
                    'messages' => [
                        'data' => [
                            '*' => [
                                'id',
                                'content',
                                'created_at',
                                'read_at',
                                'sender_type',
                                'sender_id'
                            ]
                        ],
                        'next_page_url',
                        'prev_page_url'
                    ]
                ]
            ])
            ->assertJsonCount(50, 'data.messages.data')
            ->assertJsonPath('data.messages.next_page_url', fn($url) => !is_null($url));

        // Fetch second page
        $nextPageUrl = $response->json('data.messages.next_page_url');
        $response = $this->getJson($nextPageUrl);
        
        $remainingMessages = $totalMessages - 50; // Calculate remaining messages after first page
        
        $response->assertOk()
            ->assertJsonCount($remainingMessages, 'data.messages.data') // Should have remaining 26 messages
            ->assertJsonPath('data.messages.next_page_url', null);
    }

    public function test_conversation_client_and_details_are_cached(): void
    {
        /** @var \App\Models\User $artist */
        $artist = User::factory()->create(['role' => 'artist']);
        
        /** @var \App\Models\User $client */
        $client = User::factory()->create(['role' => 'client']);
        
        $conversation = Conversation::factory()
            ->has(ConversationDetails::factory()->state([
                'phone' => '1234567890',
                'email' => 'client@example.com',
                'instagram' => '@client'
            ]))
            ->create([
                'artist_id' => $artist->id,
                'client_id' => $client->id
            ]);

        $this->actingAs($artist);
        
        // First request should cache the data
        Cache::shouldReceive('remember')
            ->withArgs(function($key, $ttl, $callback) use ($conversation) {
                return in_array($key, [
                    "conversation.{$conversation->id}.messages.page.1",
                    "conversation.{$conversation->id}.client_details"
                ]);
            })
            ->twice()
            ->andReturnUsing(function($key, $ttl, $callback) {
                return $callback();
            });

        $this->getJson("/api/conversations/{$conversation->id}")
            ->assertOk();

        // Second request should use the same cache
        Cache::shouldReceive('remember')
            ->withArgs(function($key, $ttl, $callback) use ($conversation) {
                return in_array($key, [
                    "conversation.{$conversation->id}.messages.page.1",
                    "conversation.{$conversation->id}.client_details"
                ]);
            })
            ->twice()
            ->andReturnUsing(function($key, $ttl, $callback) {
                return $callback();
            });

        $this->getJson("/api/conversations/{$conversation->id}")
            ->assertOk();
    }
}
