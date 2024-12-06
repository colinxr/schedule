<?php

namespace App\Observers;

use App\Models\Appointment;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        //
    }

    public function updated(Appointment $appointment): void
    {
        //
    }

    public function deleted(Appointment $appointment): void
    {
        //
    }

    public function restored(Appointment $appointment): void
    {
        //
    }

    public function forceDeleted(Appointment $appointment): void
    {
        //
    }
}
