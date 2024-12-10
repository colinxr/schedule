<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

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

        $this->assertEquals(
            $start->toRfc3339String(),
            $appointment->starts_at
        );
        $this->assertEquals(
            $end->toRfc3339String(),
            $appointment->ends_at
        );
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

    public function test_can_calculate_default_deposit_amount(): void
    {
        $artist = User::factory()->create(['role' => 'artist']);
        $profile = Profile::factory()->create([
            'user_id' => $artist->id,
            'settings' => ['deposit_percentage' => 40]
        ]);

        $appointment = Appointment::factory()->create([
            'artist_id' => $artist->id,
            'price' => 100.00
        ]);

        $this->assertEquals(40.00, $appointment->calculateDefaultDepositAmount());
    }

    public function test_returns_null_deposit_amount_when_no_price(): void
    {
        $artist = User::factory()->create(['role' => 'artist']);
        Profile::factory()->create([
            'user_id' => $artist->id,
            'settings' => ['deposit_percentage' => 40]
        ]);

        $appointment = Appointment::factory()->create([
            'artist_id' => $artist->id,
            'price' => null
        ]);

        $this->assertNull($appointment->calculateDefaultDepositAmount());
    }

    public function test_can_mark_deposit_as_paid(): void
    {
        $appointment = Appointment::factory()->create([
            'deposit_paid_at' => null
        ]);

        $this->assertFalse($appointment->isDepositPaid());
        
        $appointment->markDepositAsPaid();
        
        $this->assertTrue($appointment->isDepositPaid());
        $this->assertNotNull($appointment->deposit_paid_at);
    }

    public function test_can_get_remaining_balance(): void
    {
        $appointment = Appointment::factory()->create([
            'price' => 100.00,
            'deposit_amount' => 30.00
        ]);

        $this->assertEquals(70.00, $appointment->getRemainingBalance());
    }

    public function test_remaining_balance_equals_price_when_no_deposit(): void
    {
        $appointment = Appointment::factory()->create([
            'price' => 100.00,
            'deposit_amount' => null
        ]);

        $this->assertEquals(100.00, $appointment->getRemainingBalance());
    }

    public function test_remaining_balance_is_null_when_no_price(): void
    {
        $appointment = Appointment::factory()->create([
            'price' => null,
            'deposit_amount' => null
        ]);

        $this->assertNull($appointment->getRemainingBalance());
    }
} 