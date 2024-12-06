<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Conversation;
use App\Services\AppointmentService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AppointmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private AppointmentService $appointmentService
    ) {}

    public function index()
    {
        $appointments = $this->appointmentService->getUserAppointments(Auth::user());
        return response()->json(['data' => $appointments]);
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        $appointment = $this->appointmentService->getAppointmentWithDetails($appointment);
        return response()->json(['data' => $appointment]);
    }

    public function store(StoreAppointmentRequest $request)
    {
        try {
            $conversation = Conversation::findOrFail($request->validated('conversation_id'));
            
            $appointment = $this->appointmentService->createAppointment(
                $request->validated(),
                Auth::user(),
                $conversation
            );

            return response()->json(['data' => $appointment], Response::HTTP_CREATED);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Conversation not found'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create appointment',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        try {
            $appointment = $this->appointmentService->updateAppointment(
                $appointment,
                $request->validated()
            );

            return response()->json(['data' => $appointment]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update appointment',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        try {
            $this->appointmentService->deleteAppointment($appointment);
            return response()->json(null, Response::HTTP_NO_CONTENT);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete appointment',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 