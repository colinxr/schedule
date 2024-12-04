<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'artist_id' => User::factory()->create(['role' => 'artist']),
            'status' => fake()->randomElement(['pending', 'active', 'closed']),
            'last_message_at' => fake()->optional()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending'
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active'
        ]);
    }

    public function closed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed'
        ]);
    }
}
