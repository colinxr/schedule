<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public array $changedAttributes
    ) {}
} 