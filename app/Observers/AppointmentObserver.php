<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;

class AppointmentObserver
{
    private GoogleCalendarService $calendarService;

    public function __construct(GoogleCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    public function created(Appointment $appointment): void
    {
        try {
            $eventId = $this->calendarService->createEvent($appointment);
            if ($eventId) {
                $appointment->update(['google_event_id' => $eventId]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync appointment creation with Google Calendar', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updated(Appointment $appointment): void
    {
        // Only sync with Google Calendar if relevant fields have changed
        if ($appointment->wasChanged(['starts_at', 'ends_at'])) {
            try {
                $this->calendarService->updateEvent($appointment);
            } catch (\Exception $e) {
                Log::error('Failed to sync appointment update with Google Calendar', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function deleted(Appointment $appointment): void
    {
        try {
            $this->calendarService->deleteEvent($appointment);
        } catch (\Exception $e) {
            Log::error('Failed to sync appointment deletion with Google Calendar', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Appointment "restored" event.
     */
    public function restored(Appointment $appointment): void
    {
        //
    }

    /**
     * Handle the Appointment "force deleted" event.
     */
    public function forceDeleted(Appointment $appointment): void
    {
        //
    }
}
