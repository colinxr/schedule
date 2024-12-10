<?php

namespace App\Http\Controllers\Api\Artist;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    /**
     * Display the specified client's details.
     */
    public function show(User $client): JsonResponse
    {
        if (!Auth::user() || Auth::user()->role !== 'artist') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (!Auth::user()->hasAccessToClient($client)) {
            return response()->json([
                'message' => 'You do not have access to this client\'s information.'
            ], 403);
        }

        // Load the client's conversations and appointments with the current artist
        $client->load([
            'conversations' => function ($query) {
                $query->where('artist_id', Auth::id());
            },
            'clientAppointments' => function ($query) {
                $query->where('artist_id', Auth::id());
            }
        ]);

        return response()->json([
            'data' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'conversations' => $client->conversations,
                'appointments' => $client->clientAppointments,
            ]
        ]);
    }
} 