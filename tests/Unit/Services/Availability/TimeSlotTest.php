<?php

namespace Tests\Unit\Services\Availability;

use App\Services\Availability\TimeSlot;
use Carbon\Carbon;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TimeSlotTest extends TestCase
{
    #[Test]
    public function it_detects_overlapping_slots()
    {
        // Arrange
        $slot1 = new TimeSlot(
            Carbon::parse('2024-01-20 10:00:00'),
            Carbon::parse('2024-01-20 11:00:00'),
            60
        );

        $slot2 = new TimeSlot(
            Carbon::parse('2024-01-20 10:30:00'),
            Carbon::parse('2024-01-20 11:30:00'),
            60
        );

        // Act & Assert
        $this->assertTrue($slot1->overlaps($slot2));
    }

    #[Test]
    public function it_detects_non_overlapping_slots()
    {
        // Arrange
        $slot1 = new TimeSlot(
            Carbon::parse('2024-01-20 10:00:00'),
            Carbon::parse('2024-01-20 11:00:00'),
            60
        );

        $slot2 = new TimeSlot(
            Carbon::parse('2024-01-20 11:00:00'),
            Carbon::parse('2024-01-20 12:00:00'),
            60
        );

        // Act & Assert
        $this->assertFalse($slot1->overlaps($slot2));
    }

    #[Test]
    public function it_converts_to_array_format()
    {
        // Arrange
        $start = Carbon::parse('2024-01-20 10:00:00');
        $end = Carbon::parse('2024-01-20 11:00:00');
        $slot = new TimeSlot($start, $end, 60);

        // Act
        $array = $slot->toArray();

        // Assert
        $this->assertEquals([
            'starts_at' => '2024-01-20 10:00:00',
            'ends_at' => '2024-01-20 11:00:00',
            'duration' => 60
        ], $array);
    }

    #[Test]
    public function it_provides_access_to_properties()
    {
        // Arrange
        $start = Carbon::parse('2024-01-20 10:00:00');
        $end = Carbon::parse('2024-01-20 11:00:00');
        $slot = new TimeSlot($start, $end, 60);

        // Act & Assert
        $this->assertEquals($start, $slot->getStart());
        $this->assertEquals($end, $slot->getEnd());
        $this->assertEquals(60, $slot->getDuration());
    }
} 