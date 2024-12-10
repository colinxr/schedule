<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\IntakeForm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IntakeForm>
 */
class IntakeFormFactory extends Factory
{
    protected $model = IntakeForm::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'description' => fake()->paragraph(),
            'placement' => fake()->randomElement(['Upper Arm', 'Forearm', 'Back', 'Chest', 'Leg', 'Ankle']),
            'size' => fake()->randomElement(['Small (2-3 inches)', 'Medium (4-6 inches)', 'Large (7-10 inches)', 'Extra Large (11+ inches)']),
            'reference_images' => fake()->optional()->randomElements([
                'reference-images/test1.jpg',
                'reference-images/test2.jpg',
                'reference-images/test3.jpg',
            ], 2),
            'budget_range' => fake()->randomElement(['$100-300', '$301-500', '$501-1000', '$1000+']),
            'email' => fake()->safeEmail(),
        ];
    }
}
