<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_belongs_to_conversation(): void
    {
        $conversation = Conversation::factory()
            ->for(User::factory()->create(['role' => 'artist']), 'artist')
            ->for(User::factory()->create(['role' => 'client']), 'client')
            ->create();

        $appointment = Appointment::factory()
            ->for($conversation)
            ->create();

        $this->assertTrue($appointment->conversation->is($conversation));
    }

    public function test_appointment_belongs_to_artist(): void
    {
        $artist = User::factory()->create(['role' => 'artist']);
        $appointment = Appointment::factory()
            ->for($artist, 'artist')
            ->create();

        $this->assertTrue($appointment->artist->is($artist));
    }

    public function test_appointment_belongs_to_client(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $appointment = Appointment::factory()
            ->for($client, 'client')
            ->create();

        $this->assertTrue($appointment->client->is($client));
    }

    public function test_appointment_has_required_dates(): void
    {
        $start = now()->addDay();
        $end = $start->copy()->addHours(2);

        $appointment = Appointment::factory()
            ->create([
                'starts_at' => $start,
                'ends_at' => $end,
            ]);

        $this->assertEquals($start->timestamp, $appointment->starts_at->timestamp);
        $this->assertEquals($end->timestamp, $appointment->ends_at->timestamp);
    }

    public function test_appointment_can_access_reference_photos_through_conversation(): void
    {
        $conversation = Conversation::factory()
            ->create();
        
        $conversation->details()->create([
            'description' => 'Test',
            'email' => 'test@example.com',
            'reference_images' => ['image1.jpg', 'image2.jpg']
        ]);

        $appointment = Appointment::factory()
            ->for($conversation)
            ->create();

        $this->assertIsArray($appointment->conversation->details->reference_images);
        $this->assertCount(2, $appointment->conversation->details->reference_images);
    }
} 