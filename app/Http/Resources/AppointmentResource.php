<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'artist_id' => $this->artist_id,
            'client_id' => $this->client_id,
            'conversation_id' => $this->conversation_id,
            'artist' => [
                'id' => $this->artist->id,
                'name' => $this->artist->name,
            ],
            'client' => [
                'id' => $this->client->id,
                'name' => $this->client->name,
            ],
        ];
    }
} 