<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ConversationController extends Controller
{
    private $service;

    public function __construct(ConversationService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'artist_id' => 'required|exists:users,id',
            'description' => 'required|string',
            'reference_images' => 'nullable|array',
            'reference_images.*' => 'image|max:5120', // 5MB max per image
            'email' => 'required|email',
        ]);

        try {
            $conversation = $this->service->createConversation($validated);

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
        try {
            $conversation = $this->service->findConversation($id);
            
            // Check if user is authorized to view this conversation
            if ($conversation->artist_id !== auth()->id()) {
                return response()->json([
                    'message' => 'You are not authorized to view this conversation'
                ], Response::HTTP_FORBIDDEN);
            }

            return new ConversationResource($conversation);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Conversation not found',
                'error' => $e->getMessage()
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
