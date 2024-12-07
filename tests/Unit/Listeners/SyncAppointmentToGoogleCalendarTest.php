<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use App\Events\AppointmentCreated;
use App\Events\AppointmentUpdated;
use App\Events\AppointmentDeleted;
use App\Listeners\SyncAppointmentToGoogleCalendar;
use App\Models\Appointment;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Exceptions\Appointment\GoogleCalendarSyncException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SyncAppointmentToGoogleCalendarTest extends TestCase
{
    use RefreshDatabase;

    private GoogleCalendarService $googleCalendarService;
    private SyncAppointmentToGoogleCalendar $listener;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->googleCalendarService = Mockery::mock(GoogleCalendarService::class);
        $this->listener = new SyncAppointmentToGoogleCalendar($this->googleCalendarService);
    }

    #[Test]
    public function it_syncs_new_appointment_to_google_calendar()
    {
        $artist = User::factory()->create([
            'role' => 'artist',
            'google_calendar_id' => 'calendar_id'
        ]);

        $appointment = Appointment::factory()->create(['artist_id' => $artist->id]);
        
        $this->googleCalendarService
            ->shouldReceive('createEvent')
            ->once()
            ->with($appointment)
            ->andReturn('google_event_id');

        $this->listener->handleCreated(new AppointmentCreated($appointment));

        $this->assertEquals('google_event_id', $appointment->fresh()->google_event_id);
    }

    #[Test]
    public function it_skips_sync_when_artist_has_no_google_calendar()
    {
        $artist = User::factory()->create([
            'role' => 'artist',
            'google_calendar_id' => null
        ]);

        $appointment = Appointment::factory()->create(['artist_id' => $artist->id]);
        
        $this->googleCalendarService->shouldNotReceive('createEvent');

        $this->listener->handleCreated(new AppointmentCreated($appointment));
    }

    #[Test]
    public function it_throws_exception_when_google_calendar_sync_fails()
    {
        $this->expectException(GoogleCalendarSyncException::class);

        $artist = User::factory()->create([
            'role' => 'artist',
            'google_calendar_id' => 'calendar_id'
        ]);

        $appointment = Appointment::factory()->create(['artist_id' => $artist->id]);
        
        $this->googleCalendarService
            ->shouldReceive('createEvent')
            ->once()
            ->with($appointment)
            ->andReturn(false);

        $this->listener->handleCreated(new AppointmentCreated($appointment));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
} 