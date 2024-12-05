<?php

namespace App\Services;

use App\Events\ConversationCreated;
use App\Repositories\ConversationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class ConversationService
{
    public function __construct(
        private ConversationRepository $repository,
        private UserService $userService
    ) {}

    public function createConversation(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Create client user first
            $client = $this->userService->createClient([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
            ]);

            // Create conversation
            $conversation = $this->repository->create([
                'artist_id' => $data['artist_id'],
                'client_id' => $client->id,
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
                'phone' => $data['phone'] ?? null,
                'reference_images' => $referenceImages,
            ]);

            // Dispatch event
            Event::dispatch(new ConversationCreated([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ], $conversation->id));

            return $conversation->load(['details', 'client']);
        });
    }

    public function findConversation(int $id): ?\App\Models\Conversation
    {
        return $this->repository->findWithDetails($id);
    }

    public function getArtistConversations(int $artistId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getArtistConversations($artistId);
    }
} 