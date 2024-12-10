<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'client' => [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'details' => [
                    'phone' => $this->client->details->phone ?? null,
                    'email' => $this->client->details->email ?? null,
                    'instagram' => $this->client->details->instagram ?? null,
                ],
            ],
            'messages' => [
                'data' => $this->whenLoaded('messages', function() {
                    return $this->messages->map(fn($message) => [
                        'id' => $message->id,
                        'content' => $message->content,
                        'created_at' => $message->created_at,
                        'read_at' => $message->read_at,
                        'sender_type' => $message->sender_type,
                        'sender_id' => $message->sender_id,
                    ]);
                }),
                'next_page_url' => $this->when(
                    $this->messages instanceof \Illuminate\Pagination\LengthAwarePaginator,
                    fn() => $this->messages->nextPageUrl()
                ),
                'prev_page_url' => $this->when(
                    $this->messages instanceof \Illuminate\Pagination\LengthAwarePaginator,
                    fn() => $this->messages->previousPageUrl()
                ),
            ],
        ];
    }
}
