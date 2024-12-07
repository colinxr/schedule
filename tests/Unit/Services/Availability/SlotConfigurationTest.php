<?php

namespace Tests\Unit\Services\Availability;

use App\Services\Availability\SlotConfiguration;
use Tests\TestCase;

class SlotConfigurationTest extends TestCase
{
    /** @test */
    public function it_creates_valid_configuration()
    {
        // Act
        $config = new SlotConfiguration(
            duration: 60,
            buffer: 30,
            limit: 10,
            interval: 30
        );

        // Assert
        $this->assertEquals(60, $config->duration);
        $this->assertEquals(30, $config->buffer);
        $this->assertEquals(10, $config->limit);
        $this->assertEquals(30, $config->interval);
    }

    /** @test */
    public function it_validates_duration_range()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duration must be between 30 and 480 minutes');

        // Act
        new SlotConfiguration(duration: 20);
    }

    /** @test */
    public function it_validates_buffer_range()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Buffer must be between 0 and 120 minutes');

        // Act
        new SlotConfiguration(duration: 60, buffer: 150);
    }

    /** @test */
    public function it_validates_limit_range()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be between 1 and 100 slots');

        // Act
        new SlotConfiguration(duration: 60, limit: 150);
    }

    /** @test */
    public function it_validates_interval_range()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Interval must be between 15 and 60 minutes');

        // Act
        new SlotConfiguration(duration: 60, interval: 10);
    }

    /** @test */
    public function it_allows_null_limit()
    {
        // Act
        $config = new SlotConfiguration(duration: 60, limit: null);

        // Assert
        $this->assertNull($config->limit);
    }
} 