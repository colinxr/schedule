<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\Availability\TimeSlot;
use App\Services\Availability\SlotConfiguration;
use App\Services\Availability\AppointmentCache;
use App\Repositories\AppointmentRepository;
use App\Services\Availability\TimePreferenceStrategy;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function __construct(
        private readonly AppointmentCache $cache,
        private readonly AppointmentRepository $appointments,
        private readonly ?TimePreferenceStrategy $timePreference = null
    ) {}

    /**
     * Find available appointment slots for an artist
     */
    public function findAvailableSlots(
        User $artist,
        int $duration,
        Carbon $date,
        ?int $limit = null,
        ?int $buffer = 0
    ): Collection {
        $config = new SlotConfiguration($duration, $buffer, $limit);
        $workSchedules = $this->getArtistWorkSchedules($artist);
        
        if (!$workSchedules->has($date->dayOfWeek)) {
            return collect();
        }

        $availableSlots = $this->generateAvailableSlots(
            $artist,
            $workSchedules,
            $date,
            $config
        );

        return $this->applyPreferences($availableSlots);
    }

    /**
     * Get artist's work schedules
     */
    private function getArtistWorkSchedules(User $artist): Collection
    {
        return WorkSchedule::getCachedSchedule($artist->id)
            ->keyBy('day_of_week');
    }

    /**
     * Generate available slots based on work schedule and appointments
     */
    private function generateAvailableSlots(
        User $artist,
        Collection $workSchedules,
        Carbon $date,
        SlotConfiguration $config
    ): Collection {
        $daySchedule = $workSchedules->get($date->dayOfWeek);
        if (!$daySchedule) {
            return collect();
        }

        return $this->generateDaySlots(
            $artist,
            $date,
            $daySchedule,
            $config
        );
    }

    /**
     * Generate slots for a specific day
     */
    private function generateDaySlots(
        User $artist,
        Carbon $date,
        WorkSchedule $schedule,
        SlotConfiguration $config
    ): Collection {
        $dayStart = $this->calculateDayStart($date, $schedule->start_time);
        $dayEnd = $date->copy()->setTimeFrom(Carbon::parse($schedule->end_time));
        
        // Adjust end time to account for appointment duration
        $dayEnd = $dayEnd->subMinutes($config->duration);
        
        // Calculate available intervals
        $intervals = collect();
        $current = $dayStart->copy();
        
        while ($current->lte($dayEnd)) {
            $intervals->push($current->copy());
            $current->addMinutes($config->interval);
        }
        
        $appointments = $this->cache->getArtistAppointments($artist->id, $date);
        
        $slots = $this->filterAvailableSlots($intervals, $appointments, $config);
        
        return $slots->take($config->limit ?? PHP_INT_MAX);
    }

    /**
     * Calculate the start time for a day
     */
    private function calculateDayStart(Carbon $date, string $scheduleStart): Carbon
    {
        $start = $date->copy()->setTimeFrom(Carbon::parse($scheduleStart));
        
        if ($date->isToday() && now()->gt($start)) {
            return now()->ceil(30 * 60);
        }
        
        return $start;
    }

    /**
     * Filter available slots based on existing appointments
     */
    private function filterAvailableSlots(
        Collection $intervals,
        Collection $appointments,
        SlotConfiguration $config
    ): Collection {
        $appointmentSlots = $appointments->map(function ($apt) use ($config) {
            $start = Carbon::parse($apt->starts_at);
            $end = Carbon::parse($apt->ends_at);
            
            if ($config->buffer > 0) {
                $start = $start->copy()->subMinutes($config->buffer);
                $end = $end->copy()->addMinutes($config->buffer);
            }
            
            return new TimeSlot($start, $end, 0);
        });

        return $intervals
            ->map(fn ($start) => new TimeSlot(
                $start,
                $start->copy()->addMinutes($config->duration),
                $config->duration
            ))
            ->filter(fn (TimeSlot $slot) => 
                !$appointmentSlots->contains(fn (TimeSlot $apt) => $slot->overlaps($apt))
            )
            ->map->toArray();
    }

    /**
     * Apply time preferences to slots if strategy exists
     */
    private function applyPreferences(Collection $slots): Collection
    {
        if ($this->timePreference) {
            return $this->timePreference->filterSlots($slots);
        }
        
        return $slots;
    }
} 