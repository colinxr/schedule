<?php

namespace Tests\Feature\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Conversation;
use App\Services\AppointmentService;
use App\Events\AppointmentCreated;
use App\Events\AppointmentUpdated;
use App\Events\AppointmentDeleted;
use App\Exceptions\Appointment\AppointmentCreationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Carbon\Carbon;

class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $service;
    private User $artist;
    private User $client;
    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(AppointmentService::class);
        
        $this->artist = User::factory()->create(['role' => 'artist']);
        $this->client = User::factory()->create(['role' => 'client']);
        
        $this->conversation = Conversation::factory()->create([
            'artist_id' => $this->artist->id,
            'client_id' => $this->client->id,
        ]);
    }

    #[Test]
    public function it_creates_an_appointment_and_dispatches_event()
    {
        Event::fake();

        $appointmentData = [
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ];

        $appointment = $this->service->createAppointment(
            $appointmentData,
            $this->artist,
            $this->conversation
        );

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'artist_id' => $this->artist->id,
            'client_id' => $this->client->id,
            'conversation_id' => $this->conversation->id,
        ]);

        Event::assertDispatched(AppointmentCreated::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id;
        });
    }

    #[Test]
    public function it_updates_an_appointment_and_dispatches_event()
    {
        Event::fake();

        $startDate = now()->addDay()->startOfHour();
        $endDate = $startDate->copy()->addHours(2);

        $appointment = Appointment::factory()->create([
            'artist_id' => $this->artist->id,
            'client_id' => $this->client->id,
            'conversation_id' => $this->conversation->id,
            'starts_at' => $startDate,
            'ends_at' => $endDate,
        ]);

        $newStartDate = now()->addDays(2)->startOfHour();
        $newEndDate = $newStartDate->copy()->addHours(2);

        $updateData = [
            'starts_at' => $newStartDate->toDateTimeString(),
            'ends_at' => $newEndDate->toDateTimeString(),
        ];

        $updatedAppointment = $this->service->updateAppointment($appointment, $updateData);

        // Parse the dates for comparison
        $updatedStartsAt = Carbon::parse($updatedAppointment->starts_at);
        $updatedEndsAt = Carbon::parse($updatedAppointment->ends_at);

        // Assert the dates were updated correctly
        $this->assertTrue(
            $updatedStartsAt->equalTo($newStartDate) &&
            $updatedEndsAt->equalTo($newEndDate)
        );

        // Assert the event was dispatched with the changes
        Event::assertDispatched(AppointmentUpdated::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id
                && isset($event->changedAttributes['starts_at'])
                && isset($event->changedAttributes['ends_at']);
        });
    }

    #[Test]
    public function it_deletes_an_appointment_and_dispatches_event()
    {
        Event::fake();

        $appointment = Appointment::factory()->create([
            'artist_id' => $this->artist->id,
            'client_id' => $this->client->id,
            'conversation_id' => $this->conversation->id,
        ]);

        $this->service->deleteAppointment($appointment);

        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);

        Event::assertDispatched(AppointmentDeleted::class, function ($event) use ($appointment) {
            return $event->appointment->id === $appointment->id;
        });
    }

    #[Test]
    public function it_throws_exception_when_non_artist_tries_to_create_appointment()
    {
        $this->expectException(AppointmentCreationException::class);

        $nonArtist = User::factory()->create(['role' => 'client']);

        $this->service->createAppointment(
            ['starts_at' => now(), 'ends_at' => now()->addHour()],
            $nonArtist,
            $this->conversation
        );
    }

    #[Test]
    public function it_throws_exception_when_artist_creates_appointment_for_wrong_conversation()
    {
        $this->expectException(AppointmentCreationException::class);

        $otherArtist = User::factory()->create(['role' => 'artist']);
        
        $this->service->createAppointment(
            ['starts_at' => now(), 'ends_at' => now()->addHour()],
            $otherArtist,
            $this->conversation
        );
    }

    #[Test]
    public function it_retrieves_appointments_for_artist()
    {
        $appointments = Appointment::factory()->count(3)->create([
            'artist_id' => $this->artist->id,
        ]);

        $result = $this->service->getUserAppointments($this->artist);

        $this->assertCount(3, $result);
        $this->assertEquals(
            $appointments->pluck('id')->sort()->values(),
            $result->pluck('id')->sort()->values()
        );
    }

    #[Test]
    public function it_retrieves_appointments_for_client()
    {
        $appointments = Appointment::factory()->count(3)->create([
            'client_id' => $this->client->id,
        ]);

        $result = $this->service->getUserAppointments($this->client);

        $this->assertCount(3, $result);
        $this->assertEquals(
            $appointments->pluck('id')->sort()->values(),
            $result->pluck('id')->sort()->values()
        );
    }

    #[Test]
    public function it_retrieves_appointment_with_all_relations()
    {
        $conversation = Conversation::factory()
            ->withDetails()
            ->create([
                'artist_id' => $this->artist->id,
                'client_id' => $this->client->id,
            ]);

        $appointment = Appointment::factory()->create([
            'artist_id' => $this->artist->id,
            'client_id' => $this->client->id,
            'conversation_id' => $conversation->id,
        ]);

        $loadedAppointment = $this->service->getAppointmentWithDetails($appointment);

        $this->assertTrue($loadedAppointment->relationLoaded('artist'));
        $this->assertTrue($loadedAppointment->relationLoaded('client'));
        $this->assertTrue($loadedAppointment->relationLoaded('conversation'));
        $this->assertTrue($loadedAppointment->conversation->relationLoaded('details'));
    }
}
