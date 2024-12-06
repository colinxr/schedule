<?php

namespace Database\Factories;

use App\Models\ConversationDetails;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationDetailsFactory extends Factory
{
    protected $model = ConversationDetails::class;

    public function definition()
    {
        return [
            'conversation_id' => null,
            'email' => $this->faker->email(),
            'description' => $this->faker->sentence(),
            'reference_images' => null,
        ];
    }
}
