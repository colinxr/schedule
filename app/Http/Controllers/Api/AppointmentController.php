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
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Appointment\UpdateAppointmentPriceRequest;
use App\Http\Requests\Appointment\UpdateAppointmentDepositRequest;

class AppointmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private AppointmentService $appointmentService)
    {
    }

    public function index(): JsonResponse
    {
        $appointments = $this->appointmentService->getUserAppointments(Auth::user());
        return response()->json([
            'data' => AppointmentResource::collection($appointments->load(['artist', 'client']))
        ]);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        $this->authorize('view', $appointment);
        return response()->json([
            'data' => new AppointmentResource($appointment)
        ]);
    }

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $this->authorize('create', Appointment::class);
        
        $conversation = Conversation::findOrFail($request->conversation_id);
        
        $appointment = $this->appointmentService->createAppointment(
            $request->validated(),
            Auth::user(),
            $conversation
        );

        return response()->json([
            'data' => new AppointmentResource($appointment)
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);
        
        $appointment = $this->appointmentService->updateAppointment($appointment, $request->validated());

        return response()->json([
            'data' => new AppointmentResource($appointment)
        ]);
    }

    public function destroy(Appointment $appointment): Response
    {
        $this->authorize('delete', $appointment);
        
        $this->appointmentService->deleteAppointment($appointment);

        return response()->noContent();
    }

    public function updatePrice(UpdateAppointmentPriceRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment->update(['price' => $request->price]);
        $appointment->refresh();

        // Calculate and set the default deposit
        $defaultDeposit = $appointment->calculateDefaultDepositAmount();
        $appointment->update(['deposit_amount' => $defaultDeposit]);
        $appointment->refresh();

        return response()->json([
            'data' => [
                'price' => number_format($appointment->price, 2),
                'deposit_amount' => number_format($appointment->deposit_amount, 2),
                'remaining_balance' => $appointment->getRemainingBalance()
            ]
        ]);
    }

    public function updateDeposit(UpdateAppointmentDepositRequest $request, Appointment $appointment): JsonResponse
    {
        if (is_null($appointment->price)) {
            return response()->json([
                'message' => 'Cannot set deposit amount without a price.'
            ], 422);
        }

        $appointment->update(['deposit_amount' => $request->deposit_amount]);
        $appointment->refresh();

        return response()->json([
            'data' => [
                'price' => number_format($appointment->price, 2),
                'deposit_amount' => number_format($appointment->deposit_amount, 2),
                'remaining_balance' => $appointment->getRemainingBalance()
            ]
        ]);
    }

    public function toggleDepositPaid(Appointment $appointment): JsonResponse
    {
        $this->authorize('update', $appointment);

        if (is_null($appointment->deposit_amount)) {
            return response()->json([
                'message' => 'Cannot mark deposit as paid when no deposit amount is set.'
            ], 422);
        }

        if ($appointment->isDepositPaid()) {
            $appointment->update(['deposit_paid_at' => null]);
        } else {
            $appointment->markDepositAsPaid();
        }

        return response()->json([
            'data' => [
                'deposit_paid_at' => $appointment->deposit_paid_at?->toIso8601String(),
                'is_deposit_paid' => $appointment->isDepositPaid(),
                'deposit_amount' => number_format($appointment->deposit_amount, 2),
                'remaining_balance' => $appointment->getRemainingBalance()
            ]
        ]);
    }
} 