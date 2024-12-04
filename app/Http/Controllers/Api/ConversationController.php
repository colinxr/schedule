<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Services\ConversationService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    public function __construct(
        private ConversationService $conversationService
    ) {}

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

    public function show(int $id)
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $conversation = $this->conversationService->findConversation($id);
            
            if ($conversation->artist_id !== Auth::id()) {
                return response()->json([
                    'message' => 'You are not authorized to view this conversation'
                ], Response::HTTP_FORBIDDEN);
            }

            return new ConversationResource($conversation->load(['details', 'artist']));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Conversation not found',
                'error' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
