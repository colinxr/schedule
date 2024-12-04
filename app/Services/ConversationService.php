<?php

namespace App\Services;

use App\Repositories\ConversationRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    public function __construct(
        private ConversationRepository $repository
    ) {}

    public function createConversation(array $validatedData, ?array $referenceImages = []): array
    {
        try {
            $processedImages = $this->handleImageUploads($referenceImages);
            
            $result = DB::transaction(function () use ($validatedData, $processedImages) {
                return $this->repository->create([
                    ...$validatedData,
                    'reference_images' => $processedImages,
                ]);
            });

            return [
                'success' => true,
                'conversation' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function handleImageUploads(?array $images): array
    {
        if (empty($images)) {
            return [];
        }

        return array_map(function (UploadedFile $image) {
            return $image->store('reference-images', 'public');
        }, $images);
    }
} 