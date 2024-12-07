<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $artist;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->artist = User::factory()->create(['role' => 'artist']);
    }

    public function test_artist_can_set_work_schedule()
    {
        $this->actingAs($this->artist);

        $scheduleData = [
            'schedules' => [
                [
                    'day_of_week' => 2, // Tuesday
                    'start_time' => '11:00',
                    'end_time' => '18:00',
                    'timezone' => 'America/New_York'
                ],
                [
                    'day_of_week' => 3, // Wednesday
                    'start_time' => '11:00',
                    'end_time' => '18:00',
                    'timezone' => 'America/New_York'
                ],
                [
                    'day_of_week' => 4, // Thursday
                    'start_time' => '11:00',
                    'end_time' => '18:00',
                    'timezone' => 'America/New_York'
                ],
                [
                    'day_of_week' => 5, // Friday
                    'start_time' => '11:00',
                    'end_time' => '18:00',
                    'timezone' => 'America/New_York'
                ],
                [
                    'day_of_week' => 6, // Saturday
                    'start_time' => '11:00',
                    'end_time' => '18:00',
                    'timezone' => 'America/New_York'
                ]
            ]
        ];

        $response = $this->postJson('/api/schedule', $scheduleData);

        $response->assertStatus(201)
            ->assertJsonCount(5, 'schedules');

        $this->assertDatabaseCount('work_schedules', 5);
        $this->assertDatabaseHas('work_schedules', [
            'user_id' => $this->artist->id,
            'day_of_week' => 2,
            'start_time' => '11:00:00',
            'end_time' => '18:00:00',
            'timezone' => 'America/New_York'
        ]);
    }

    public function test_artist_can_update_specific_day_schedule()
    {
        $this->actingAs($this->artist);
        
        $schedule = WorkSchedule::factory()->create([
            'user_id' => $this->artist->id,
            'day_of_week' => 2,
            'start_time' => '11:00',
            'end_time' => '18:00'
        ]);

        $updateData = [
            'start_time' => '12:00',
            'end_time' => '19:00',
        ];

        $response = $this->putJson("/api/schedule/{$schedule->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('work_schedules', [
            'id' => $schedule->id,
            'start_time' => '12:00:00',
            'end_time' => '19:00:00'
        ]);
    }

    public function test_artist_can_delete_specific_day_schedule()
    {
        $this->actingAs($this->artist);
        
        $schedule = WorkSchedule::factory()->create([
            'user_id' => $this->artist->id,
            'day_of_week' => 2,
        ]);

        $response = $this->deleteJson("/api/schedule/{$schedule->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('work_schedules', ['id' => $schedule->id]);
    }

    public function test_artist_can_get_their_work_schedule()
    {
        $this->actingAs($this->artist);
        
        WorkSchedule::factory()
            ->count(5)
            ->sequential()
            ->create([
                'user_id' => $this->artist->id
            ]);

        $response = $this->getJson('/api/schedule');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'schedules');
    }

    public function test_non_artists_cannot_set_work_schedule()
    {
        /** @var \App\Models\User $client */
        $client = User::factory()->create(['role' => 'client']);
        $this->actingAs($client);

        $scheduleData = [
            'schedules' => [
                [
                    'day_of_week' => 2,
                    'start_time' => '11:00',
                    'end_time' => '18:00',
                    'timezone' => 'America/New_York'
                ]
            ]
        ];

        $response = $this->postJson('/api/schedule', $scheduleData);

        $response->assertStatus(403);
    }

    public function test_validates_work_schedule_input()
    {
        $this->actingAs($this->artist);

        $scheduleData = [
            'schedules' => [
                [
                    'day_of_week' => 8, // Invalid day
                    'start_time' => '25:00', // Invalid time
                    'end_time' => '18:00',
                ]
            ]
        ];

        $response = $this->postJson('/api/schedule', $scheduleData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['schedules.0.day_of_week', 'schedules.0.start_time']);
    }

    public function test_artist_cannot_set_duplicate_day_schedules()
    {
        $this->actingAs($this->artist);

        $scheduleData = [
            'schedules' => [
                [
                    'day_of_week' => 2,
                    'start_time' => '11:00',
                    'end_time' => '18:00',
                    'timezone' => 'America/New_York'
                ],
                [
                    'day_of_week' => 2, // Duplicate day
                    'start_time' => '12:00',
                    'end_time' => '19:00',
                    'timezone' => 'America/New_York'
                ]
            ]
        ];

        $response = $this->postJson('/api/schedule', $scheduleData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['schedules']);
    }

    public function test_artist_can_update_existing_day_schedule()
    {
        $this->actingAs($this->artist);

        // Create initial schedule
        WorkSchedule::factory()->create([
            'user_id' => $this->artist->id,
            'day_of_week' => 2,
            'start_time' => '11:00',
            'end_time' => '18:00'
        ]);

        // Try to set new schedule for same day
        $scheduleData = [
            'schedules' => [
                [
                    'day_of_week' => 2,
                    'start_time' => '12:00',
                    'end_time' => '19:00',
                    'timezone' => 'America/New_York'
                ]
            ]
        ];

        $response = $this->postJson('/api/schedule', $scheduleData);

        $response->assertStatus(201);
        
        // Assert old schedule was replaced
        $this->assertDatabaseCount('work_schedules', 1);
        $this->assertDatabaseHas('work_schedules', [
            'user_id' => $this->artist->id,
            'day_of_week' => 2,
            'start_time' => '12:00:00',
            'end_time' => '19:00:00'
        ]);
    }
} 