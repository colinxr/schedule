<?php

namespace App\Http\Controllers\Api\Artist;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\AppointmentResource;

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
        $client->loadMissing([
            'conversations' => function ($query) {
                $query->select('id', 'client_id', 'artist_id', 'status', 'created_at', 'last_message_at')
                    ->where('artist_id', Auth::id())
                    ->latest('last_message_at')
                    ->with(['details:id,conversation_id,phone,email,instagram'])
                    ->paginate(10);
            },
            'clientAppointments' => function ($query) {
                $query->select('id', 'client_id', 'artist_id', 'starts_at', 'ends_at', 'status', 'price', 'deposit_amount', 'deposit_paid_at')
                    ->where('artist_id', Auth::id())
                    ->latest('starts_at')
                    ->paginate(10);
            }
        ]);

        return response()->json([
            'data' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'conversations' => ConversationResource::collection($client->conversations),
                'appointments' => AppointmentResource::collection($client->clientAppointments),
            ]
        ]);
    }
} 