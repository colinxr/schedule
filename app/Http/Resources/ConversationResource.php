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
            'last_message_at' => $this->last_message_at,
            'artist' => [
                'id' => $this->artist->id,
                'name' => $this->artist->name,
                'email' => $this->artist->email,
            ],
            'intake_form' => [
                'description' => $this->intakeForm->description,
                'placement' => $this->intakeForm->placement,
                'size' => $this->intakeForm->size,
                'reference_images' => $this->intakeForm->reference_images,
                'budget_range' => $this->intakeForm->budget_range,
                'email' => $this->intakeForm->email,
            ],
        ];
    }
}
