<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\GetAvailableSlotsRequest;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Support\TimeslotPaginator;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\AvailabilityResource;

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

        $paginatedResults = $this->availabilityService->getAvailableSlots(
            $artist,
            $request->date,
            $request->duration,
            $request->page ?? 1,
            $request->per_page ?? 10,
            $request->limit
        );

        return response()->json(new AvailabilityResource($paginatedResults));
    }
} 