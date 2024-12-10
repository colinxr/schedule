<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ConversationController extends Controller
{
    public function __construct(
        private ConversationService $conversationService
    ) {}

    public function index()
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $conversations = Cache::remember(
            "user.".Auth::id().".conversations",
            now()->addMinutes(5),
            fn() => $this->conversationService->getArtistConversations(Auth::id())
        );

        return ConversationResource::collection($conversations);
    }

    public function show(Conversation $conversation)
    {
        abort_unless(Auth::id() === $conversation->artist_id, 403);

        $messages = Cache::remember(
            "conversation.{$conversation->id}.messages.page." . request('page', 1),
            now()->addMinute(),
            fn() => $conversation->messages()
                ->select('id', 'conversation_id', 'content', 'created_at', 'read_at', 'sender_type', 'sender_id')
                ->with(['sender:id,first_name,last_name'])
                ->latest()
                ->paginate(50)
        );

        $clientDetails = Cache::remember(
            "conversation.{$conversation->id}.client_details",
            now()->addHour(),
            fn() => $conversation->client()
                ->select('id', 'first_name', 'last_name', 'email')
                ->with(['details:id,user_id,phone,instagram'])
                ->first()
        );

        $conversation->setRelation('messages', $messages);
        $conversation->setRelation('client', $clientDetails);
        
        return new ConversationResource($conversation);
    }

    public function store(StoreConversationRequest $request)
    {
        try {
            $conversation = $this->conversationService->createConversation(
                $request->validated()
            );

            Cache::forget("user.".Auth::id().".conversations");

            return response()->json([
                'message' => 'Conversation started successfully',
                'data' => new ConversationResource($conversation)
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create conversation',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
