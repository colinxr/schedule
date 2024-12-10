<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'profile' => $this->whenLoaded('profile', function() {
                return [
                    'id' => $this->profile->id,
                    'settings' => $this->profile->settings,
                    'created_at' => $this->profile->created_at,
                    'updated_at' => $this->profile->updated_at,
                ];
            }),
            'google_calendar_id' => $this->when($this->role === 'artist', $this->google_calendar_id),
            'google_token_expires_at' => $this->when($this->role === 'artist', fn() => $this->google_token_expires_at?->toIso8601String()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 