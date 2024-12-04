<?php

namespace App\Repositories;

use App\Models\Conversation;

class ConversationRepository
{
    public function create(array $data): Conversation
    {
        $conversation = Conversation::create([
            'artist_id' => $data['artist_id'],
            'status' => 'pending',
        ]);

        $conversation->details()->create([
            ...$data,
            'reference_images' => $data['reference_images'] ?? [],
        ]);

        return $conversation->load(['artist:id,name,email', 'details']);
    }

    public function find(int $id)
    {
        return Conversation::with(['artist:id,name,email', 'details'])->findOrFail($id);
    }
} 