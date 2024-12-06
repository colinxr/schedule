<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Conversation;
use App\Models\User;
use App\Events\AppointmentCreated;
use App\Events\AppointmentUpdated;
use App\Events\AppointmentDeleted;
use App\Exceptions\Appointment\AppointmentCreationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class AppointmentService
{
    public function getUserAppointments(User $user): Collection
    {
        return $user->role === 'artist'
            ? $user->appointments()->with('client')->latest()->get()
            : $user->clientAppointments()->with('artist')->latest()->get();
    }

    public function createAppointment(array $data, User $artist, Conversation $conversation): Appointment
    {
        if ($artist->role !== 'artist') {
            throw AppointmentCreationException::invalidArtist();
        }

        if ($conversation->artist_id !== $artist->id) {
            throw AppointmentCreationException::invalidConversation();
        }

        return DB::transaction(function () use ($data, $artist, $conversation) {
            $appointment = Appointment::create([
                ...$data,
                'artist_id' => $artist->id,
                'client_id' => $conversation->client_id,
                'conversation_id' => $conversation->id,
            ]);

            AppointmentCreated::dispatch($appointment);

            return $appointment;
        });
    }

    public function updateAppointment(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            $changedAttributes = array_intersect_key($data, $appointment->getDirty());
            
            $appointment->fill($data);
            $appointment->save();

            AppointmentUpdated::dispatch($appointment, $changedAttributes);

            return $appointment;
        });
    }

    public function deleteAppointment(Appointment $appointment): void
    {
        DB::transaction(function () use ($appointment) {
            $appointment->delete();

            AppointmentDeleted::dispatch($appointment);
        });
    }

    public function getAppointmentWithDetails(Appointment $appointment): Appointment
    {
        return $appointment->load(['artist', 'client', 'conversation.details']);
    }
}
