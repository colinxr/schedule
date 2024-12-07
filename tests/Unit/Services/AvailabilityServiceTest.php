<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\WorkSchedule;
use App\Models\Appointment;
use App\Services\AvailabilityService;
use App\Services\Availability\AppointmentCache;
use App\Services\Availability\TimePreferenceStrategy;
use App\Services\Availability\MorningPreference;
use App\Repositories\AppointmentRepository;
use Carbon\Carbon;
use Tests\TestCase;
use Mockery;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AvailabilityServiceTest extends TestCase
{
    use DatabaseTransactions;

    private AvailabilityService $service;
    private AppointmentCache $cache;
    private AppointmentRepository $repository;
    private ?TimePreferenceStrategy $timePreference;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = Mockery::mock(AppointmentCache::class);
        $this->repository = Mockery::mock(AppointmentRepository::class);
        $this->timePreference = null;

        $this->service = new AvailabilityService(
            $this->cache,
            $this->repository,
            $this->timePreference
        );
    }

    /** @test */
    public function it_returns_empty_collection_when_no_work_schedule_exists()
    {
        // Arrange
        $artist = User::factory()->create(['role' => 'artist']);
        $date = Carbon::parse('2024-01-20'); // Saturday

        // Act
        $slots = $this->service->findAvailableSlots(
            artist: $artist,
            duration: 60,
            date: $date
        );

        // Assert
        $this->assertEmpty($slots);
    }

    /** @test */
    public function it_finds_available_slots_for_work_day()
    {
        // Arrange
        $artist = User::factory()->create(['role' => 'artist']);
        $date = Carbon::parse('2024-01-20'); // Saturday

        WorkSchedule::factory()->create([
            'user_id' => $artist->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00'
        ]);

        $this->cache->expects('getArtistAppointments')
            ->with($artist->id, Mockery::on(fn ($arg) => $arg instanceof Carbon && $arg->isSameDay($date)))
            ->once()
            ->andReturn(collect([]));

        // Act
        $slots = $this->service->findAvailableSlots(
            artist: $artist,
            duration: 60,
            date: $date
        );

        // Assert
        // 8 hours (480 minutes) minus 60 minutes for duration = 420 minutes
        // 420 minutes with 30-minute intervals = 15 slots
        $this->assertCount(15, $slots);
        $this->assertEquals(
            '2024-01-20 09:00:00',
            Carbon::parse($slots->first()['starts_at'])->toDateTimeString()
        );
    }

    /** @test */
    public function it_respects_buffer_time_between_appointments()
    {
        // Arrange
        $artist = User::factory()->create(['role' => 'artist']);
        $date = Carbon::parse('2024-01-20');

        WorkSchedule::factory()->create([
            'user_id' => $artist->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00'
        ]);

        $appointment = new Appointment([
            'artist_id' => $artist->id,
            'starts_at' => '2024-01-20 11:00:00',
            'ends_at' => '2024-01-20 12:00:00'
        ]);

        $this->cache->expects('getArtistAppointments')
            ->with($artist->id, Mockery::on(fn ($arg) => $arg instanceof Carbon && $arg->isSameDay($date)))
            ->once()
            ->andReturn(collect([$appointment]));

        // Act
        $slots = $this->service->findAvailableSlots(
            artist: $artist,
            duration: 60,
            date: $date,
            buffer: 30
        );

        // Assert
        $slotTimes = $slots->pluck('starts_at')->map(fn ($time) => 
            Carbon::parse($time)->format('Y-m-d H:i:s')
        );
        
        $this->assertNotContains('2024-01-20 10:30:00', $slotTimes);
        $this->assertNotContains('2024-01-20 12:00:00', $slotTimes);
    }

    /** @test */
    public function it_applies_time_preference_filtering()
    {
        // Arrange
        $artist = User::factory()->create(['role' => 'artist']);
        $date = Carbon::parse('2024-01-20');

        WorkSchedule::factory()->create([
            'user_id' => $artist->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00'
        ]);

        $this->cache->expects('getArtistAppointments')
            ->with($artist->id, Mockery::on(fn ($arg) => $arg instanceof Carbon && $arg->isSameDay($date)))
            ->once()
            ->andReturn(collect([]));

        $service = new AvailabilityService(
            $this->cache,
            $this->repository,
            new MorningPreference()
        );

        // Act
        $slots = $service->findAvailableSlots(
            artist: $artist,
            duration: 60,
            date: $date
        );

        // Assert
        foreach ($slots as $slot) {
            $hour = Carbon::parse($slot['starts_at'])->hour;
            $this->assertLessThan(12, $hour);
        }
    }

    /** @test */
    public function it_respects_slot_limit()
    {
        // Arrange
        $artist = User::factory()->create(['role' => 'artist']);
        $date = Carbon::parse('2024-01-20');

        WorkSchedule::factory()->create([
            'user_id' => $artist->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '17:00'
        ]);

        $this->cache->expects('getArtistAppointments')
            ->with($artist->id, Mockery::on(fn ($arg) => $arg instanceof Carbon && $arg->isSameDay($date)))
            ->once()
            ->andReturn(collect([]));

        // Act
        $slots = $this->service->findAvailableSlots(
            artist: $artist,
            duration: 60,
            date: $date,
            limit: 5
        );

        // Assert
        $this->assertCount(5, $slots);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
} 