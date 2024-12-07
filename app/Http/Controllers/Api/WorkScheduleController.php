<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorkSchedule\StoreWorkScheduleRequest;
use App\Http\Requests\WorkSchedule\UpdateWorkScheduleRequest;
use App\Models\WorkSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class WorkScheduleController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $schedules = Auth::user()->workSchedules()->get();
        
        return response()->json([
            'schedules' => $schedules
        ]);
    }

    public function store(StoreWorkScheduleRequest $request): JsonResponse
    {
        $schedules = collect($request->schedules)->map(function ($schedule) {
            return Auth::user()->workSchedules()->create($schedule);
        });

        return response()->json([
            'schedules' => $schedules
        ], 201);
    }

    public function update(UpdateWorkScheduleRequest $request, WorkSchedule $workSchedule): JsonResponse
    {
        $workSchedule->update($request->validated());

        return response()->json([
            'schedule' => $workSchedule
        ]);
    }

    public function destroy(WorkSchedule $workSchedule): JsonResponse
    {
        $this->authorize('delete', $workSchedule);
        
        $workSchedule->delete();

        return response()->json([
            'message' => 'Schedule deleted successfully'
        ]);
    }
} 