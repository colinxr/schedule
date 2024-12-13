<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request)
    {
        $conversation = Conversation::findOrFail($request->conversation_id);
        
        // Ensure user has access to this conversation
        abort_unless(Auth::id() === $conversation->artist_id, 403);

        try {
            $message = Message::create($request->validated());
            
            // Update conversation's last_message_at
            $conversation->update(['last_message_at' => now()]);
            
            // Clear conversation cache
            Cache::forget("conversation.{$conversation->id}.messages.page.1");
            
            return response()->json([
                'message' => 'Message sent successfully',
                'data' => [
                    'id' => $message->id,
                    'content' => $message->content,
                    'created_at' => $message->created_at,
                    'read_at' => $message->read_at,
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send message',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}