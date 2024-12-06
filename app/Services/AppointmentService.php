<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\User;
use App\Exceptions\Appointment\GoogleCalendarSyncException;
use App\Exceptions\Appointment\AppointmentCreationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class AppointmentService
{
    public function __construct(
        private GoogleCalendarService $calendarService
    ) {}

    public function getUserAppointments(User $user): Collection
    {
        return $user->role === 'artist'
            ? $user->appointments()->with('client')->latest()->get()
            : $user->clientAppointments()->with('artist')->latest()->get();
    }

    public function createAppointment(array $data, User $artist, Conversation $conversation): Appointment
    {
        if ($artist->role !== 'artist') {
            throw AppointmentCreationException::invalidArtist();
        }

        if ($conversation->artist_id !== $artist->id) {
            throw AppointmentCreationException::invalidConversation();
        }

        return DB::transaction(function () use ($data, $artist, $conversation) {
            // Create appointment
            $appointment = Appointment::create([
                ...$data,
                'artist_id' => $artist->id,
                'client_id' => $conversation->client_id,
            ]);

            // Create Google Calendar event if artist has calendar connected
            if ($artist->google_calendar_id) {
                $eventId = $this->calendarService->createEvent($appointment);
                
                if (!$eventId) {
                    throw GoogleCalendarSyncException::failedToCreate();
                }

                $appointment->google_event_id = $eventId;
                $appointment->save();
            }

            return $appointment;
        });
    }

    public function updateAppointment(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            $appointment->fill($data);

            // If dates changed and calendar is connected, update Google Calendar
            if ($appointment->isDirty(['starts_at', 'ends_at']) && 
                $appointment->artist->google_calendar_id) {
                
                $success = $this->calendarService->updateEvent($appointment);
                
                if (!$success) {
                    throw GoogleCalendarSyncException::failedToUpdate();
                }
            }

            $appointment->save();
            return $appointment;
        });
    }

    public function deleteAppointment(Appointment $appointment): void
    {
        DB::transaction(function () use ($appointment) {
            // If calendar is connected, delete Google Calendar event first
            if ($appointment->google_event_id && $appointment->artist->google_calendar_id) {
                $success = $this->calendarService->deleteEvent($appointment);
                
                if (!$success) {
                    throw GoogleCalendarSyncException::failedToDelete();
                }
            }

            $appointment->delete();
        });
    }

    public function getAppointmentWithDetails(Appointment $appointment): Appointment
    {
        return $appointment->load(['artist', 'client', 'conversation.details']);
    }
}
