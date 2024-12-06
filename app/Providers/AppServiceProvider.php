<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Repositories\ConversationRepositoryInterface;
use App\Repositories\ConversationRepository;
use App\Services\ConversationService;
use App\Models\Conversation;
use App\Models\ConversationDetails;
use App\Models\Message;
use App\Observers\ConversationObserver;
use App\Observers\ConversationDetailsObserver;
use App\Observers\MessageObserver;
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
        ConversationDetails::observe(ConversationDetailsObserver::class);
        Conversation::observe(ConversationObserver::class);
        Message::observe(MessageObserver::class);
    }
}
