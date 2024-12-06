<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $sender = User::factory()->create();
        
        return [
            'conversation_id' => Conversation::factory(),
            'content' => $this->faker->paragraph(),
            'sender_type' => User::class,
            'sender_id' => $sender->id,
            'read_at' => $this->faker->optional()->dateTimeBetween('-1 week'),
            'created_at' => $this->faker->dateTimeBetween('-1 week'),
        ];
    }
} 