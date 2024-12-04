<?php

namespace App\Repositories;

use App\Models\Conversation;

class ConversationRepository
{
    public function findWithDetails(int $id): ?Conversation
    {
        return Conversation::with(['details', 'artist'])
            ->findOrFail($id);
    }

    public function create(array $data): Conversation
    {
        return Conversation::create($data);
    }

    public function getArtistConversations(int $artistId): \Illuminate\Database\Eloquent\Collection
    {
        return Conversation::with(['details', 'messages' => function ($query) {
                $query->latest()->take(1);
            }])
            ->where('artist_id', $artistId)
            ->latest('last_message_at')
            ->get();
    }
} 