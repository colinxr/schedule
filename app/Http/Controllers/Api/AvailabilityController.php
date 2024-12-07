<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\GetAvailableSlotsRequest;
use App\Jobs\CalculateArtistAvailability;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Support\TimeslotPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

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

        $date = $request->validated('date') ? now()->parse($request->validated('date')) : now();
        $duration = $request->validated('duration');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        // Try to get pre-computed slots from cache
        $cacheKey = "artist_availability:{$artist->id}";
        $cachedAvailability = Cache::get($cacheKey);

        if ($cachedAvailability && isset($cachedAvailability[$date->format('Y-m-d')][$duration])) {
            $slots = $cachedAvailability[$date->format('Y-m-d')][$duration];
        } else {
            // If not in cache, calculate on-demand and dispatch background job to update cache
            $slots = $this->availabilityService->findAvailableSlots(
                artist: $artist,
                duration: $duration,
                date: $date,
                limit: null // Get all slots for the day
            );

            // Dispatch job to update cache in background
            CalculateArtistAvailability::dispatch($artist);
        }

        // Paginate the slots
        $paginatedResults = TimeslotPaginator::paginate($slots, $page, $perPage);

        return response()->json([
            'available_slots' => $paginatedResults['data'],
            'pagination' => $paginatedResults['pagination']
        ]);
    }
} 