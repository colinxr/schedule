<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkSchedule>
 */
class WorkScheduleFactory extends Factory
{
    protected $model = WorkSchedule::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'timezone' => 'America/New_York',
            'is_active' => true,
        ];
    }

    /**
     * Configure the factory to create sequential days.
     */
    public function sequential(int $startDay = 0): static
    {
        return $this->sequence(
            ...collect()
                ->range(0, 4)
                ->map(fn ($index) => [
                    'day_of_week' => $startDay + $index
                ])
                ->all()
        );
    }
} 