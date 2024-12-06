<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Repositories\ConversationRepositoryInterface;
use App\Repositories\ConversationRepository;
use App\Services\ConversationService;
use App\Services\AppointmentService;
use App\Services\GoogleCalendarService;
use App\Models\Conversation;
use App\Models\ConversationDetails;
use App\Models\Message;
use App\Models\Appointment;
use App\Observers\ConversationObserver;
use App\Observers\ConversationDetailsObserver;
use App\Observers\MessageObserver;
use App\Observers\AppointmentObserver;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ConversationRepositoryInterface::class, ConversationRepository::class);
        $this->app->singleton(ConversationService::class);
        
        // Register Google Calendar Service as singleton
        $this->app->singleton(GoogleCalendarService::class);
        
        // Register Appointment Service
        $this->app->singleton(AppointmentService::class, function ($app) {
            return new AppointmentService(
                $app->make(GoogleCalendarService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ConversationDetails::observe(ConversationDetailsObserver::class);
        Conversation::observe(ConversationObserver::class);
        Message::observe(MessageObserver::class);
        Appointment::observe(AppointmentObserver::class);
    }
}
