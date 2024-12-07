<?php

namespace App\Services\Availability;

use Carbon\Carbon;

class TimeSlot
{
    public function __construct(
        private readonly Carbon $start,
        private readonly Carbon $end,
        private readonly int $duration
    ) {}

    public function overlaps(TimeSlot $other): bool
    {
        return ($this->start >= $other->start && $this->start < $other->end) ||
               ($this->end > $other->start && $this->end <= $other->end) ||
               ($this->start <= $other->start && $this->end >= $other->end);
    }

    public function toArray(): array
    {
        return [
            'starts_at' => $this->start->toDateTimeString(),
            'ends_at' => $this->end->toDateTimeString(),
            'duration' => $this->duration
        ];
    }

    public function getStart(): Carbon
    {
        return $this->start;
    }

    public function getEnd(): Carbon
    {
        return $this->end;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }
} 