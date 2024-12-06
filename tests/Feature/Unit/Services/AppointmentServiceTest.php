<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Conversation;
use App\Services\AppointmentService;
use App\Services\GoogleCalendarService;
use App\Exceptions\Appointment\GoogleCalendarSyncException;
use App\Exceptions\Appointment\AppointmentCreationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentService $service;
    private GoogleCalendarService $calendarService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->calendarService = Mockery::mock(GoogleCalendarService::class);
        $this->service = new AppointmentService($this->calendarService);
    }

    public function test_can_get_artist_appointments()
    {
        $artist = User::factory()->create(['role' => 'artist']);
        $appointments = Appointment::factory()
            ->count(3)
            ->for($artist, 'artist')
            ->create();

        // Create appointments for another artist (should not be returned)
        Appointment::factory()->count(2)->create();

        $result = $this->service->getUserAppointments($artist);

        $this->assertCount(3, $result);
        $this->assertTrue($result->first()->artist->is($artist));
    }

    public function test_can_get_client_appointments()
    {
        $client = User::factory()->create(['role' => 'client']);
        $appointments = Appointment::factory()
            ->count(2)
            ->for($client, 'client')
            ->create();

        // Create appointments for another client (should not be returned)
        Appointment::factory()->count(3)->create();

        $result = $this->service->getUserAppointments($client);

        $this->assertCount(2, $result);
        $this->assertTrue($result->first()->client->is($client));
    }

    public function test_can_create_appointment_without_google_calendar()
    {
        $artist = User::factory()->create(['role' => 'artist']);
        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->create();

        $appointmentData = [
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ];

        $appointment = $this->service->createAppointment($appointmentData, $artist, $conversation);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'artist_id' => $artist->id,
            'client_id' => $conversation->client_id,
        ]);
    }

    public function test_can_create_appointment_with_google_calendar()
    {
        $artist = User::factory()->create([
            'role' => 'artist',
            'google_calendar_id' => 'calendar_id'
        ]);
        
        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->create();

        $appointmentData = [
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ];

        $this->calendarService
            ->shouldReceive('createEvent')
            ->once()
            ->andReturn('event_id');

        $appointment = $this->service->createAppointment($appointmentData, $artist, $conversation);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'google_event_id' => 'event_id',
        ]);
    }

    public function test_throws_exception_when_google_calendar_sync_fails()
    {
        $artist = User::factory()->create([
            'role' => 'artist',
            'google_calendar_id' => 'calendar_id'
        ]);
        
        $conversation = Conversation::factory()
            ->for($artist, 'artist')
            ->create();

        $this->calendarService
            ->shouldReceive('createEvent')
            ->once()
            ->andReturn(null);

        $this->expectException(GoogleCalendarSyncException::class);

        $this->service->createAppointment([
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ], $artist, $conversation);
    }

    public function test_only_artists_can_create_appointments()
    {
        $client = User::factory()->create(['role' => 'client']);
        $conversation = Conversation::factory()->create();

        $this->expectException(AppointmentCreationException::class);

        $this->service->createAppointment([
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ], $client, $conversation);
    }

    public function test_artist_can_only_create_appointments_for_own_conversations()
    {
        $artist = User::factory()->create(['role' => 'artist']);
        $conversation = Conversation::factory()->create(); // Different artist's conversation

        $this->expectException(AppointmentCreationException::class);

        $this->service->createAppointment([
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHours(2),
        ], $artist, $conversation);
    }

    public function test_can_update_appointment()
    {
        $appointment = Appointment::factory()->create();
        $newData = ['starts_at' => now()->addDays(2)];

        $updatedAppointment = $this->service->updateAppointment($appointment, $newData);

        $this->assertEquals(
            $newData['starts_at']->toDateTimeString(),
            $updatedAppointment->starts_at->toDateTimeString()
        );
    }

    public function test_updates_google_calendar_when_dates_change()
    {
        $artist = User::factory()->create(['google_calendar_id' => 'calendar_id']);
        $appointment = Appointment::factory()
            ->for($artist, 'artist')
            ->create(['google_event_id' => 'event_id']);

        $this->calendarService
            ->shouldReceive('updateEvent')
            ->once()
            ->andReturn(true);

        $this->service->updateAppointment($appointment, [
            'starts_at' => now()->addDays(2),
        ]);
    }

    public function test_throws_exception_when_google_calendar_update_fails()
    {
        $artist = User::factory()->create(['google_calendar_id' => 'calendar_id']);
        $appointment = Appointment::factory()
            ->for($artist, 'artist')
            ->create(['google_event_id' => 'event_id']);

        $this->calendarService
            ->shouldReceive('updateEvent')
            ->once()
            ->andReturn(false);

        $this->expectException(GoogleCalendarSyncException::class);

        $this->service->updateAppointment($appointment, [
            'starts_at' => now()->addDays(2),
        ]);
    }

    public function test_can_delete_appointment()
    {
        $appointment = Appointment::factory()->create();

        $this->service->deleteAppointment($appointment);

        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    public function test_deletes_google_calendar_event_when_deleting_appointment()
    {
        $artist = User::factory()->create(['google_calendar_id' => 'calendar_id']);
        $appointment = Appointment::factory()
            ->for($artist, 'artist')
            ->create(['google_event_id' => 'event_id']);

        $this->calendarService
            ->shouldReceive('deleteEvent')
            ->once()
            ->andReturn(true);

        $this->service->deleteAppointment($appointment);

        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    public function test_throws_exception_when_google_calendar_delete_fails()
    {
        $artist = User::factory()->create(['google_calendar_id' => 'calendar_id']);
        $appointment = Appointment::factory()
            ->for($artist, 'artist')
            ->create(['google_event_id' => 'event_id']);

        $this->calendarService
            ->shouldReceive('deleteEvent')
            ->once()
            ->andReturn(false);

        $this->expectException(GoogleCalendarSyncException::class);

        $this->service->deleteAppointment($appointment);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}
