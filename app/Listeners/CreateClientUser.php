<?php

namespace App\Listeners;

use App\Events\ConversationCreated;

class CreateClientUser
{
    public function handle(ConversationCreated $event): void
    {
        // Handle any additional processing after conversation and user creation
        // For example: Send welcome email, notifications, etc.
    }
}
