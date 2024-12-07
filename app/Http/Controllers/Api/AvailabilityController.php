<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\GetAvailableSlotsRequest;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService
    ) {}

    public function getAvailableSlots(GetAvailableSlotsRequest $request, User $artist): JsonResponse
    {
        if ($artist->role !== 'artist') {
            return response()->json([
                'message' => 'User is not an artist'
            ], 404);
        }

        $availableSlots = $this->availabilityService->findAvailableSlots(
            artist: $artist,
            duration: $request->validated('duration'),
            date: $request->validated('date') ? now()->parse($request->validated('date')) : now(),
            limit: $request->validated('limit', 5)
        );

        return response()->json([
            'available_slots' => $availableSlots
        ]);
    }
} 