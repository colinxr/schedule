<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'artist_id' => User::factory()->create(['role' => 'artist'])->id,
            'status' => 'pending',
            'last_message_at' => now(),
        ];
    }
}
