<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Repositories\ConversationRepositoryInterface;
use App\Repositories\ConversationRepository;
use App\Services\ConversationService;
use App\Models\Conversation;
use App\Models\ConversationDetails;
use App\Observers\ConversationObserver;
use App\Observers\ConversationDetailsObserver;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Log::info('Registering ConversationDetails observer');
        ConversationDetails::observe(ConversationDetailsObserver::class);
        Conversation::observe(ConversationObserver::class);
    }
}
