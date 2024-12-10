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
            'artist_id' => $this->artist_id,
            'client_id' => $this->client_id,
            'conversation_id' => $this->conversation_id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'price' => $this->when($this->price !== null, fn() => number_format($this->price, 2)),
            'deposit_amount' => $this->when($this->deposit_amount !== null, fn() => number_format($this->deposit_amount, 2)),
            'deposit_paid_at' => $this->when($this->deposit_paid_at !== null, fn() => $this->deposit_paid_at->toIso8601String()),
            'is_deposit_paid' => $this->when($this->deposit_amount !== null, fn() => $this->isDepositPaid()),
            'remaining_balance' => $this->when($this->price !== null, fn() => $this->getRemainingBalance()),
            'artist' => $this->whenLoaded('artist', fn() => [
                'id' => $this->artist->id,
                'name' => $this->artist->first_name . ' ' . $this->artist->last_name,
                'first_name' => $this->artist->first_name,
                'last_name' => $this->artist->last_name,
                'email' => $this->artist->email,
            ]),
            'client' => $this->whenLoaded('client', fn() => [
                'id' => $this->client->id,
                'name' => $this->client->first_name . ' ' . $this->client->last_name,
                'first_name' => $this->client->first_name,
                'last_name' => $this->client->last_name,
                'email' => $this->client->email,
            ]),
            'conversation' => $this->whenLoaded('conversation', fn() => [
                'id' => $this->conversation->id,
                'status' => $this->conversation->status,
            ]),
        ];
    }
} 