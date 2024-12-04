<?php

namespace App\Repositories;

use App\Models\Conversation;

class ConversationRepository
{
    public function findWithDetails(int $id): ?Conversation
    {
        return Conversation::with(['details', 'artist:id,name,email'])
            ->findOrFail($id);
    }

    public function create(array $data): Conversation
    {
        return Conversation::create($data);
    }
} 