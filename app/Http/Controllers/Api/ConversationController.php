<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Services\ConversationService;
use App\Models\Conversation;

class ConversationController extends Controller
{
    public function __construct(
        private ConversationService $conversationService
    ) {}

    public function store(StoreConversationRequest $request)
    {
        $result = $this->conversationService->createConversation(
            $request->validated(),
            $request->file('reference_images')
        );

        if (!$result['success']) {
            return response()->json([
                'message' => 'Failed to start conversation',
                'error' => $result['error']
            ], 500);
        }

        return response()->json([
            'message' => 'Conversation started successfully',
            'data' => new ConversationResource($result['conversation'])
        ], 201);
    }

    public function show(Conversation $conversation)
    {
        return new ConversationResource($conversation);
    }
}
