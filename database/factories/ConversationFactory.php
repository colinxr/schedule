<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\ConversationDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'artist_id' => User::factory()->create(['role' => 'artist'])->id,
            'client_id' => User::factory()->create(['role' => 'client'])->id,
            'status' => 'pending',
            'last_message_at' => now(),
        ];
    }

    public function withDetails(): static
    {
        return $this->afterCreating(function (Conversation $conversation) {
            ConversationDetails::create([
                'conversation_id' => $conversation->id,
                'email' => fake()->email(),
                'description' => fake()->sentence(),
                'reference_images' => null,
            ]);
        });
    }

    public function withMessages(int $count = 2): static
    {
        return $this->afterCreating(function (Conversation $conversation) use ($count) {
            // Create older messages first
            for ($i = $count - 1; $i >= 0; $i--) {
                Message::factory()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $conversation->artist_id,
                    'content' => $i === 0 ? 'Latest message for conversation ' . $conversation->id : 'Older message',
                    'created_at' => now()->subHours($i),
                ]);
            }
        });
    }

    public function withDetailsAndMessages(int $messageCount = 2): static
    {
        return $this->withDetails()->withMessages($messageCount);
    }
}
