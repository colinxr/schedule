<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Models\Conversation;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AppointmentController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $user = Auth::user();
        $appointments = $user->role === 'artist' 
            ? $user->appointments()->with('client')->latest()->get()
            : $user->clientAppointments()->with('artist')->latest()->get();

        return response()->json(['data' => $appointments]);
    }

    public function show(Appointment $appointment)
    {
        $this->authorize('view', $appointment);

        return response()->json([
            'data' => $appointment->load(['artist', 'client', 'conversation.details'])
        ]);
    }

    public function store(StoreAppointmentRequest $request)
    {
        $validated = $request->validated();
        $conversation = Conversation::findOrFail($validated['conversation_id']);

        $appointment = Appointment::create([
            ...$validated,
            'artist_id' => Auth::id(),
            'client_id' => $conversation->client_id,
        ]);

        return response()->json(['data' => $appointment], Response::HTTP_CREATED);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $appointment->update($request->validated());

        return response()->json(['data' => $appointment]);
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorize('delete', $appointment);

        $appointment->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
} 