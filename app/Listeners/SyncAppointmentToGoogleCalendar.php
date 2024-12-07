<?php

namespace App\Listeners;

use App\Events\AppointmentCreated;
use App\Events\AppointmentUpdated;
use App\Events\AppointmentDeleted;
use App\Services\GoogleCalendarService;
use App\Exceptions\Appointment\GoogleCalendarSyncException;

class SyncAppointmentToGoogleCalendar
{
    public function __construct(
        private GoogleCalendarService $calendarService
    ) {}

    public function handleCreated(AppointmentCreated $event): void
    {
        $appointment = $event->appointment;
        
        if (!$appointment->artist->google_calendar_id) {
            return;
        }

        $eventId = $this->calendarService->createEvent($appointment);
        
        if (!$eventId) {
            throw GoogleCalendarSyncException::failedToCreate();
        }

        $appointment->update(['google_event_id' => $eventId]);
    }

    public function handleUpdated(AppointmentUpdated $event): void
    {
        $appointment = $event->appointment;
        
        if (!$appointment->artist->google_calendar_id ||
            !$this->shouldSyncUpdate($event->changedAttributes)) {
            return;
        }

        if (!$this->calendarService->updateEvent($appointment)) {
            throw GoogleCalendarSyncException::failedToUpdate();
        }
    }

    private function shouldSyncUpdate(array $changedAttributes): bool
    {
        return !empty(array_intersect(['starts_at', 'ends_at'], array_keys($changedAttributes)));
    }
} 