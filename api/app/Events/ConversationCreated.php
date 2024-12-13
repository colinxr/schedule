<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly array $clientData,
        public readonly int $conversationId
    ) {}
}
