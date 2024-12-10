<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Appointment;

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->artist_id || $user->id === $appointment->client_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'artist';
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->artist_id;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->artist_id;
    }
} 