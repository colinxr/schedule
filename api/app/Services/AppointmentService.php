<?php

namespace App\Services;

use App\Models\User;
use App\Models\Appointment;
use App\Models\Conversation;
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
        $query = $user->role === 'artist'
            ? $user->appointments()
            : $user->clientAppointments();

        return $query->with([
            'artist:id,first_name,last_name,email',
            'client:id,first_name,last_name,email',
            'conversation:id,artist_id,client_id,status'
        ])
        ->select([
            'id',
            'artist_id',
            'client_id',
            'conversation_id',
            'starts_at',
            'ends_at',
            'status',
            'price',
            'deposit_amount',
            'deposit_paid_at'
        ])
        ->latest('starts_at')
        ->get();
    }

    public function getAppointmentWithDetails(Appointment $appointment): Appointment
    {
        return $appointment->load([
            'artist:id,first_name,last_name,email',
            'client:id,first_name,last_name,email',
            'conversation:id,artist_id,client_id,status',
            'conversation.details:id,conversation_id,phone,email,instagram'
        ]);
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
            $appointment->fill($data);
            
            $changedAttributes = $appointment->getDirty();
            
            $appointment->save();

            AppointmentUpdated::dispatch($appointment, $changedAttributes);

            return $appointment->fresh([
                'artist:id,first_name,last_name,email',
                'client:id,first_name,last_name,email'
            ]);
        });
    }

    public function deleteAppointment(Appointment $appointment): void
    {
        DB::transaction(function () use ($appointment) {
            $appointment->delete();

            AppointmentDeleted::dispatch($appointment);
        });
    }
}
