<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'last_message_at' => $this->last_message_at,
            'artist' => [
                'id' => $this->artist->id,
                'first_name' => $this->artist->first_name,
                'last_name' => $this->artist->last_name,
                'email' => $this->artist->email,
            ],
            'details' => [
                'description' => $this->details->description,
                'reference_images' => $this->details->reference_images,
                'email' => $this->details->email,
            ],
            'latest_message' => $this->when($this->messages->isNotEmpty(), function () {
                $message = $this->messages->first();
                return [
                    'content' => Str::limit($message->content, 250),
                    'created_at' => $message->created_at,
                    'read_at' => $message->read_at,
                ];
            }),
        ];
    }
}
