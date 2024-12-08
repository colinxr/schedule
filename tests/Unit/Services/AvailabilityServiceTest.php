<?php

namespace Tests\Unit\Services;

use Mockery;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use Mockery\MockInterface;
use App\Models\WorkSchedule;
use App\Models\Appointment;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Repositories\AppointmentRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;


class AvailabilityServiceTest extends TestCase
{
    use DatabaseTransactions;

    private AvailabilityService $service;
    /** @var AppointmentRepositoryInterface&\Mockery\MockInterface */
    private MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = Mockery::mock(AppointmentRepositoryInterface::class);
        $this->service = new AvailabilityService($this->repository);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_work_schedule_exists_for_30_days()
    {
        // Arrange
        $artist = User::factory()->create(['role' => 'artist']);
        $date = Carbon::parse('2024-01-20'); // Saturday

        // No work schedules created at all

        // Mock repository to return no appointments for any date
        $this->repository->shouldReceive('getAppointmentsForDate')
            ->zeroOrMoreTimes()
            ->withAnyArgs()
            ->andReturn(collect());

        // Act
        $slots = $this->service->findAvailableSlots(
            artist: $artist,
            duration: 60,
            date: $date
        );

        // Assert
        $this->assertEmpty($slots);
    }

    #[Test]
    public function it_finds_slots_on_next_working_day()
    {
        // Arrange
        $artist = User::factory()->create(['role' => 'artist']);
        $date = Carbon::parse('2024-01-21'); // Sunday

        // Create work schedule for Monday only
        WorkSchedule::create([
            'user_id' => $artist->id,
            'day_of_week' => 1, // Monday
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true
        ]);

        // Mock repository to return no appointments for any date
        $this->repository->shouldReceive('getAppointmentsForDate')
            ->once()
            ->withArgs(function ($artist, $date) {
                return $date->format('Y-m-d') === '2024-01-22';
            })
            ->andReturn(collect());

        // Act
        $slots = $this->service->findAvailableSlots(
            artist: $artist,
            duration: 60,
            date: $date
        );

        // Assert
        $this->assertNotEmpty($slots);
        $firstSlot = $slots->first();
        $this->assertEquals(
            '2024-01-22 09:00:00', // Next day (Monday)
            $firstSlot['starts_at'],
            'First slot should be on Monday at 9 AM'
        );
    }

    #[Test]
    public function it_finds_available_slots_for_work_day()
    {
        // Arrange
        $artist = User::factory()->create(['role' => 'artist']);
        $date = Carbon::parse('2024-01-22'); // Monday

        WorkSchedule::create([
            'user_id' => $artist->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true
        ]);

        // Mock repository
        $this->repository->shouldReceive('getAppointmentsForDate')
            ->once()
            ->withArgs(function ($artist, $date) {
                return $date->format('Y-m-d') === '2024-01-22';
            })
            ->andReturn(collect());

        // Act
        $slots = $this->service->findAvailableSlots(
            artist: $artist,
            duration: 60,
            date: $date
        );

        // Assert
        $this->assertCount(8, $slots);
        $this->assertEquals(
            '2024-01-22 09:00:00',
            Carbon::parse($slots->first()['starts_at'])->toDateTimeString()
        );
    }

    #[Test]
    public function it_skips_slots_that_overlap_with_existing_appointments()
    {
        // Arrange
        $artist = User::factory()->create(['role' => 'artist']);
        $date = Carbon::parse('2024-01-22'); // Monday

        WorkSchedule::create([
            'user_id' => $artist->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true
        ]);

        // Create an existing appointment from 09:00-13:00
        $existingAppointment = new Appointment([
            'starts_at' => $date->copy()->setTimeFromTimeString('09:00'),
            'ends_at' => $date->copy()->setTimeFromTimeString('13:00')
        ]);

        // Mock repository to return existing appointment
        $this->repository->shouldReceive('getAppointmentsForDate')
            ->once()
            ->withArgs(function ($artist, $date) {
                return $date->format('Y-m-d') === '2024-01-22';
            })
            ->andReturn(collect([$existingAppointment]));

        // Act - Request 2-hour slots
        $slots = $this->service->findAvailableSlots(
            artist: $artist,
            duration: 120,
            date: $date
        );

        // Assert - First available slot should be 13:00-15:00
        $firstSlot = $slots->first();
        $this->assertEquals(
            $date->copy()->setTimeFromTimeString('13:00')->toDateTimeString(),
            $firstSlot['starts_at'],
            'First available slot should start at 13:00'
        );
        $this->assertEquals(
            $date->copy()->setTimeFromTimeString('15:00')->toDateTimeString(),
            $firstSlot['ends_at'],
            'First available slot should end at 15:00'
        );
    }

    #[Test]
    public function it_detects_overlapping_appointments()
    {
        // Arrange
        $artist = User::factory()->create(['role' => 'artist']);
        $date = Carbon::parse('2024-01-22'); // Monday

        WorkSchedule::create([
            'user_id' => $artist->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true
        ]);

        // Create existing appointments:
        // 1. 09:00-10:00
        // 2. 11:00-13:00
        // 3. 14:00-15:00
        $existingAppointments = [
            new Appointment([
                'starts_at' => $date->copy()->setTimeFromTimeString('09:00'),
                'ends_at' => $date->copy()->setTimeFromTimeString('10:00')
            ]),
            new Appointment([
                'starts_at' => $date->copy()->setTimeFromTimeString('11:00'),
                'ends_at' => $date->copy()->setTimeFromTimeString('13:00')
            ]),
            new Appointment([
                'starts_at' => $date->copy()->setTimeFromTimeString('14:00'),
                'ends_at' => $date->copy()->setTimeFromTimeString('15:00')
            ])
        ];

        // Mock repository to return existing appointments
        $this->repository->shouldReceive('getAppointmentsForDate')
            ->once()
            ->withArgs(function ($artist, $date) {
                return $date->format('Y-m-d') === '2024-01-22';
            })
            ->andReturn(collect($existingAppointments));

        // Act - Request 1-hour slots
        $slots = $this->service->findAvailableSlots(
            artist: $artist,
            duration: 60,
            date: $date
        );

        // Assert - Expected available slots: 10:00-11:00, 13:00-14:00, 15:00-16:00, 16:00-17:00
        $expectedSlots = [
            ['starts_at' => '10:00', 'ends_at' => '11:00'],
            ['starts_at' => '13:00', 'ends_at' => '14:00'],
            ['starts_at' => '15:00', 'ends_at' => '16:00'],
            ['starts_at' => '16:00', 'ends_at' => '17:00']
        ];

        $this->assertCount(count($expectedSlots), $slots);

        foreach ($slots as $index => $slot) {
            $this->assertEquals(
                $date->copy()->setTimeFromTimeString($expectedSlots[$index]['starts_at'])->toDateTimeString(),
                $slot['starts_at'],
                "Slot $index starts at wrong time"
            );
            $this->assertEquals(
                $date->copy()->setTimeFromTimeString($expectedSlots[$index]['ends_at'])->toDateTimeString(),
                $slot['ends_at'],
                "Slot $index ends at wrong time"
            );
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
} 