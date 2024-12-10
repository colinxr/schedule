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

        $conversations = $this->conversationService->getArtistConversations(Auth::id());
        return ConversationResource::collection($conversations);
    }

    public function show(Conversation $conversation)
    {
        abort_unless(Auth::id() === $conversation->artist_id, 403);

        $messages = Cache::remember(
            "conversation.{$conversation->id}.messages.page." . request('page', 1),
            now()->addMinute(),
            fn() => $conversation->messages()->paginate(50)
        );

        $clientDetails = Cache::remember(
            "conversation.{$conversation->id}.client_details",
            now()->addHour(),
            fn() => $conversation->client->load('details')
        );

        $conversation->setRelation('messages', $messages);
        
        return new ConversationResource($conversation);
    }

    public function store(StoreConversationRequest $request)
    {
        try {
            $conversation = $this->conversationService->createConversation(
                $request->validated()
            );

            return response()->json([
                'message' => 'Conversation started successfully',
                'data' => new ConversationResource($conversation)
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to start conversation',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
