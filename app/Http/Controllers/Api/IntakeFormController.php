<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\IntakeForm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntakeFormController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'artist_id' => 'required|exists:users,id',
            'description' => 'required|string',
            'placement' => 'required|string|max:255',
            'size' => 'required|string|max:255',
            'reference_images' => 'nullable|array',
            'reference_images.*' => 'image|max:5120', // 5MB max per image
            'budget_range' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        try {
            $result = DB::transaction(function () use ($validated, $request) {
                // Create conversation
                $conversation = Conversation::create([
                    'artist_id' => $validated['artist_id'],
                    'status' => 'pending',
                ]);

                // Handle file uploads
                $referenceImages = [];
                if ($request->hasFile('reference_images')) {
                    foreach ($request->file('reference_images') as $image) {
                        $path = $image->store('reference-images', 'public');
                        $referenceImages[] = $path;
                    }
                }

                // Create intake form
                $intakeForm = $conversation->intakeForm()->create([
                    ...$validated,
                    'reference_images' => $referenceImages,
                ]);

                // Load relationships for response
                $conversation->load(['artist:id,name,email', 'intakeForm']);

                return $conversation;
            });

            return response()->json([
                'message' => 'Intake form submitted successfully',
                'data' => new ConversationResource($result)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to submit intake form',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
