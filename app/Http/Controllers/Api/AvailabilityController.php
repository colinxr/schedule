<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\GetAvailableSlotsRequest;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Support\TimeslotPaginator;
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

        $date = $request->validated('date') ? now()->parse($request->validated('date')) : now();
        $duration = $request->validated('duration');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $buffer = $request->input('buffer', 0);
        $limit = $request->input('limit');

        // Calculate available slots
        $slots = $this->availabilityService->findAvailableSlots(
            artist: $artist,
            duration: $duration,
            date: $date,
            limit: $limit,
            buffer: $buffer
        );

        // Convert collection to array and paginate
        $paginatedResults = TimeslotPaginator::paginate($slots->toArray(), $page, $perPage);

        return response()->json([
            'available_slots' => $paginatedResults['data'],
            'pagination' => [
                'total' => $paginatedResults['pagination']['total'],
                'total_pages' => $paginatedResults['pagination']['total_pages'],
                'has_more_pages' => $paginatedResults['pagination']['has_more_pages'],
                'current_page' => $paginatedResults['pagination']['current_page'],
                'per_page' => $paginatedResults['pagination']['per_page'],
                'from' => $paginatedResults['pagination']['from'],
                'to' => $paginatedResults['pagination']['to']
            ]
        ]);
    }
} 