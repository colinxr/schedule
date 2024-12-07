<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Policies\AppointmentPolicy;
use App\Models\WorkSchedule;
use App\Policies\WorkSchedulePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Appointment::class => AppointmentPolicy::class,
        WorkSchedule::class => WorkSchedulePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
} 