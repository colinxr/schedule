<?php

namespace App\Services;

use App\Repositories\ConversationRepository;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    public function __construct(
        private ConversationRepository $repository
    ) {}

    public function createConversation(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Create conversation
            $conversation = $this->repository->create([
                'artist_id' => $data['artist_id'],
                'status' => 'pending',
            ]);

            // Handle reference images
            $referenceImages = [];
            if (isset($data['reference_images'])) {
                foreach ($data['reference_images'] as $image) {
                    $path = $image->store('reference-images', 'public');
                    $referenceImages[] = $path;
                }
            }

            // Create details
            $conversation->details()->create([
                'description' => $data['description'],
                'email' => $data['email'],
                'reference_images' => $referenceImages,
            ]);

            return $conversation->load(['artist:id,name,email', 'details']);
        });
    }

    public function findConversation(int $id)
    {
        return $this->repository->findWithDetails($id);
    }
} 