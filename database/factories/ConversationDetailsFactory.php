<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\ConversationDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationDetailsFactory extends Factory
{
    protected $model = ConversationDetails::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'description' => fake()->paragraph(),
            'reference_images' => fake()->optional()->randomElements([
                'reference-images/test1.jpg',
                'reference-images/test2.jpg',
                'reference-images/test3.jpg',
            ], 2),
            'email' => fake()->safeEmail(),
        ];
    }
}
