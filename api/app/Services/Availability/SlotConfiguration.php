<?php

namespace App\Services\Availability;

class SlotConfiguration
{
    public function __construct(
        public readonly int $duration,
        public readonly int $buffer = 0,
        public readonly ?int $limit = null,
        public readonly int $interval = 30
    ) {
        if ($duration < 30 || $duration > 480) {
            throw new \InvalidArgumentException('Duration must be between 30 and 480 minutes');
        }

        if ($buffer < 0 || $buffer > 120) {
            throw new \InvalidArgumentException('Buffer must be between 0 and 120 minutes');
        }

        if ($limit !== null && ($limit < 1 || $limit > 100)) {
            throw new \InvalidArgumentException('Limit must be between 1 and 100 slots');
        }

        if ($interval < 15 || $interval > 60) {
            throw new \InvalidArgumentException('Interval must be between 15 and 60 minutes');
        }
    }
} 