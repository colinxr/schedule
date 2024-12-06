<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Conversation;
use App\Services\AppointmentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Http\Resources\AppointmentResource;

class AppointmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private AppointmentService $appointmentService)
    {
        $this->authorizeResource(Appointment::class, 'appointment');
    }

    public function index(): JsonResponse
    {
        $appointments = $this->appointmentService->getUserAppointments(auth()->user());
        return response()->json([
            'data' => AppointmentResource::collection($appointments->load(['artist', 'client']))
        ]);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        return response()->json([
            'data' => new AppointmentResource($appointment)
        ]);
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $conversation = Conversation::findOrFail($request->conversation_id);
        
        $appointment = $this->appointmentService->createAppointment(
            $request->validated(),
            auth()->user(),
            $conversation
        );

        return response()->json([
            'data' => new AppointmentResource($appointment)
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment = $this->appointmentService->updateAppointment($appointment, $request->validated());

        return response()->json([
            'data' => new AppointmentResource($appointment)
        ]);
    }

    public function destroy(Appointment $appointment): Response
    {
        $this->appointmentService->deleteAppointment($appointment);

        return response()->noContent();
    }
} 