<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleCalendarService
{
    private Google_Client $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('app/google-calendar/credentials.json'));
        $this->client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
        $this->client->setAccessType('offline');
    }

    private function getCalendarService(User $artist): ?Google_Service_Calendar
    {
        if (!$artist->google_access_token) {
            return null;
        }

        try {
            $this->client->setAccessToken([
                'access_token' => $artist->google_access_token,
                'refresh_token' => $artist->google_refresh_token,
                'expires_in' => Carbon::now()->diffInSeconds($artist->google_token_expires_at),
            ]);

            // Refresh token if expired
            if ($this->client->isAccessTokenExpired()) {
                $token = $this->client->fetchAccessTokenWithRefreshToken($artist->google_refresh_token);
                
                if (isset($token['access_token'])) {
                    $artist->update([
                        'google_access_token' => $token['access_token'],
                        'google_token_expires_at' => now()->addSeconds($token['expires_in']),
                    ]);
                }
            }

            return new Google_Service_Calendar($this->client);

        } catch (\Exception $e) {
            Log::error('Failed to initialize Google Calendar service', [
                'artist_id' => $artist->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function createEvent(Appointment $appointment): ?string
    {
        $artist = $appointment->artist;
        
        if (!$artist->google_calendar_id) {
            return null;
        }

        $service = $this->getCalendarService($artist);
        if (!$service) {
            return null;
        }

        try {
            $event = new Google_Service_Calendar_Event([
                'summary' => $this->getEventSummary($appointment),
                'description' => $this->getEventDescription($appointment),
                'start' => [
                    'dateTime' => $appointment->starts_at->toRfc3339String(),
                    'timeZone' => config('app.timezone'),
                ],
                'end' => [
                    'dateTime' => $appointment->ends_at->toRfc3339String(),
                    'timeZone' => config('app.timezone'),
                ],
                'attendees' => [
                    ['email' => $appointment->client->email],
                    ['email' => $artist->email],
                ],
                'reminders' => [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 24 * 60],
                        ['method' => 'popup', 'minutes' => 30],
                    ],
                ],
            ]);

            $createdEvent = $service->events->insert($artist->google_calendar_id, $event);
            return $createdEvent->getId();

        } catch (\Exception $e) {
            Log::error('Failed to create Google Calendar event', [
                'appointment_id' => $appointment->id,
                'artist_id' => $artist->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function updateEvent(Appointment $appointment): bool
    {
        $artist = $appointment->artist;
        
        if (!$artist->google_calendar_id || !$appointment->google_event_id) {
            return false;
        }

        $service = $this->getCalendarService($artist);
        if (!$service) {
            return false;
        }

        try {
            $event = $service->events->get($artist->google_calendar_id, $appointment->google_event_id);
            
            $event->setSummary($this->getEventSummary($appointment));
            $event->setDescription($this->getEventDescription($appointment));
            
            $event->setStart(new Google_Service_Calendar_EventDateTime([
                'dateTime' => $appointment->starts_at->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ]));
            
            $event->setEnd(new Google_Service_Calendar_EventDateTime([
                'dateTime' => $appointment->ends_at->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ]));

            $service->events->update($artist->google_calendar_id, $event->getId(), $event);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update Google Calendar event', [
                'appointment_id' => $appointment->id,
                'artist_id' => $artist->id,
                'google_event_id' => $appointment->google_event_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function deleteEvent(Appointment $appointment): bool
    {
        $artist = $appointment->artist;
        
        if (!$artist->google_calendar_id || !$appointment->google_event_id) {
            return false;
        }

        $service = $this->getCalendarService($artist);
        if (!$service) {
            return false;
        }

        try {
            $service->events->delete($artist->google_calendar_id, $appointment->google_event_id);
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete Google Calendar event', [
                'appointment_id' => $appointment->id,
                'artist_id' => $artist->id,
                'google_event_id' => $appointment->google_event_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function getEventSummary(Appointment $appointment): string
    {
        return "Tattoo Appointment with {$appointment->client->name}";
    }

    private function getEventDescription(Appointment $appointment): string
    {
        $description = "Tattoo appointment details:\n\n";
        $description .= "Client: {$appointment->client->name}\n";
        $description .= "Artist: {$appointment->artist->name}\n";
        
        if ($appointment->conversation && $appointment->conversation->details) {
            $details = $appointment->conversation->details;
            if ($details->description) {
                $description .= "\nProject Description:\n{$details->description}";
            }
        }

        return $description;
    }
}
