<?php

namespace App\Observers;

use App\Models\Message;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MessageObserver
{
    /**
     * Handle the Message "created" event.
     */
    public function created(Message $message): void
    {
        // Load the conversation relationship if not loaded
        if (!$message->relationLoaded('conversation')) {
            $message->load(['conversation.artist.profile']);
        }

        // Skip notification if this is the initial conversation message
        if ($this->isInitialConversationMessage($message)) {
            return;
        }

        // Only notify the artist if the message is from the client
        if ($message->sender_id === $message->conversation->client_id) {
            $message->conversation->artist->notify(new NewMessageNotification($message));
        }
    }

    /**
     * Check if this is the first message of a conversation by checking
     * if this is the only message in the conversation
     */
    private function isInitialConversationMessage(Message $message): bool
    {
        return $message->conversation->messages()->count() === 1;
    }

    /**
     * Handle the Message "updated" event.
     */
    public function updated(Message $message): void
    {
        //
    }

    /**
     * Handle the Message "deleted" event.
     */
    public function deleted(Message $message): void
    {
        //
    }

    /**
     * Handle the Message "restored" event.
     */
    public function restored(Message $message): void
    {
        //
    }

    /**
     * Handle the Message "force deleted" event.
     */
    public function forceDeleted(Message $message): void
    {
        //
    }
}
