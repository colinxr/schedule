<?php

namespace Tests\Feature\Api;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IntakeFormControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $artist;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->artist = User::factory()->create(['role' => 'artist']);
    }

    public function test_can_submit_intake_form()
    {
        $formData = [
            'artist_id' => $this->artist->id,
            'description' => 'A beautiful rose tattoo',
            'placement' => 'Upper Arm',
            'size' => 'Medium (4-6 inches)',
            'budget_range' => '$301-500',
            'email' => 'client@example.com',
            'reference_images' => [
                UploadedFile::fake()->image('tattoo1.jpg'),
                UploadedFile::fake()->image('tattoo2.jpg'),
            ],
        ];

        $response = $this->postJson('/api/intake-forms', $formData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'status',
                    'created_at',
                    'last_message_at',
                    'artist' => ['id', 'name', 'email'],
                    'intake_form' => [
                        'description',
                        'placement',
                        'size',
                        'reference_images',
                        'budget_range',
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
        foreach ($conversation->intakeForm->reference_images as $image) {
            Storage::disk('public')->exists($image);
        }
    }

    public function test_validates_required_fields()
    {
        $response = $this->postJson('/api/intake-forms', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'artist_id',
                'description',
                'placement',
                'size',
                'budget_range',
                'email',
            ]);
    }

    public function test_validates_email_format()
    {
        $response = $this->postJson('/api/intake-forms', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
