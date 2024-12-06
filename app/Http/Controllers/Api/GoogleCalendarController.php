<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Google_Client;
use Google_Service_Calendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleCalendarController extends Controller
{
    private Google_Client $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('app/google-calendar/credentials.json'));
        $this->client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent'); // Force to get refresh token
        $this->client->setRedirectUri(config('app.url') . '/api/google/callback');
    }

    public function connect()
    {
        $user = Auth::user();
        
        if ($user->role !== 'artist') {
            return response()->json([
                'message' => 'Only artists can connect their Google Calendar'
            ], 403);
        }

        $authUrl = $this->client->createAuthUrl();
        return response()->json(['auth_url' => $authUrl]);
    }

    public function callback(Request $request)
    {
        try {
            if ($request->has('error')) {
                return response()->json([
                    'message' => 'Failed to connect Google Calendar: ' . $request->get('error')
                ], 400);
            }

            $token = $this->client->fetchAccessTokenWithAuthCode($request->get('code'));
            
            if (!isset($token['access_token'])) {
                return response()->json([
                    'message' => 'Failed to get access token'
                ], 400);
            }

            // Get user's primary calendar ID
            $service = new Google_Service_Calendar($this->client);
            $calendar = $service->calendars->get('primary');

            $user = Auth::user();
            $user->update([
                'google_calendar_id' => $calendar->getId(),
                'google_access_token' => $token['access_token'],
                'google_refresh_token' => $token['refresh_token'] ?? null,
                'google_token_expires_at' => now()->addSeconds($token['expires_in']),
            ]);

            return response()->json([
                'message' => 'Successfully connected Google Calendar'
            ]);

        } catch (\Exception $e) {
            Log::error('Google Calendar connection failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Failed to connect Google Calendar'
            ], 500);
        }
    }

    public function disconnect()
    {
        $user = Auth::user();
        
        if ($user->role !== 'artist') {
            return response()->json([
                'message' => 'Only artists can manage their Google Calendar connection'
            ], 403);
        }

        try {
            if ($user->google_access_token) {
                $this->client->revokeToken($user->google_access_token);
            }

            $user->update([
                'google_calendar_id' => null,
                'google_access_token' => null,
                'google_refresh_token' => null,
                'google_token_expires_at' => null,
            ]);

            return response()->json([
                'message' => 'Successfully disconnected Google Calendar'
            ]);

        } catch (\Exception $e) {
            Log::error('Google Calendar disconnection failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Failed to disconnect Google Calendar'
            ], 500);
        }
    }

    public function status()
    {
        $user = Auth::user();
        
        return response()->json([
            'connected' => !is_null($user->google_calendar_id),
            'calendar_id' => $user->google_calendar_id,
            'expires_at' => optional($user->google_token_expires_at)->toIso8601String(),
        ]);
    }
}
