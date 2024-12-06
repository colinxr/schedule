<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Events\AppointmentCreated;
use App\Events\AppointmentUpdated;
use App\Events\AppointmentDeleted;
use App\Listeners\SyncAppointmentToGoogleCalendar;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        AppointmentCreated::class => [
            SyncAppointmentToGoogleCalendar::class . '@handleCreated',
        ],
        AppointmentUpdated::class => [
            SyncAppointmentToGoogleCalendar::class . '@handleUpdated',
        ],
        AppointmentDeleted::class => [
            SyncAppointmentToGoogleCalendar::class . '@handleDeleted',
        ],
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
} 