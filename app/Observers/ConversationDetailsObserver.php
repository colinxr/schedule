<?php

namespace App\Observers;

use App\Models\ConversationDetails;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewConversationNotification;
use Illuminate\Support\Facades\Log;

class ConversationDetailsObserver
{
    /**
     * Handle the ConversationDetails "created" event.
     */
    public function created(ConversationDetails $details): void
    {
        Log::info('ConversationDetailsObserver created event fired', [
            'details_id' => $details->id,
            'conversation_id' => $details->conversation_id,
            'description' => $details->description,
            'has_conversation' => isset($details->conversation),
        ]);

        // Load necessary relationships
        if (!$details->relationLoaded('conversation')) {
            $details->load(['conversation.client', 'conversation.artist.profile']);
        }

        // Send the new conversation notification
        $details->conversation->artist->notify(new NewConversationNotification($details->conversation));

        if ($details->description) {
            $messageData = [
                'conversation_id' => $details->conversation_id,
                'content' => $details->description,
                'sender_type' => User::class,
                'sender_id' => $details->conversation->client_id,
            ];

            Log::info('Creating message with data', $messageData);

            try {
                Message::create($messageData);
                $details->conversation->update(['last_message_at' => now()]);
            } catch (\Exception $e) {
                Log::error('Failed to create message', [
                    'error' => $e->getMessage(),
                    'data' => $messageData
                ]);
                throw $e;
            }
        }
    }

    /**
     * Handle the ConversationDetails "updated" event.
     */
    public function updated(ConversationDetails $details): void
    {
        //
    }

    /**
     * Handle the ConversationDetails "deleted" event.
     */
    public function deleted(ConversationDetails $details): void
    {
        //
    }

    /**
     * Handle the ConversationDetails "restored" event.
     */
    public function restored(ConversationDetails $details): void
    {
        //
    }

    /**
     * Handle the ConversationDetails "force deleted" event.
     */
    public function forceDeleted(ConversationDetails $details): void
    {
        //
    }
}