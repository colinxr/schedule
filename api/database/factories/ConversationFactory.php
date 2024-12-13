<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use App\Models\ConversationDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition()
    {
        return [
            'artist_id' => User::factory()->create(['role' => 'artist'])->id,
            'client_id' => User::factory()->create(['role' => 'client'])->id,
        ];
    }

    public function withDetails()
    {
        return $this->afterCreating(function (Conversation $conversation) {
            ConversationDetails::factory()->create([
                'conversation_id' => $conversation->id,
            ]);
        });
    }
}
