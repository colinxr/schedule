<?php

namespace App\Services;

use App\Models\User;
use App\Models\Appointment;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AvailabilityService
{
    /**
     * Find available appointment slots for an artist
     */
    public function findAvailableSlots(
        User $artist,
        int $duration,
        Carbon $date,
        int $limit = 5,
        ?int $buffer = 0
    ): Collection {
        // Get the artist's work schedule from cache
        $workSchedules = WorkSchedule::getCachedSchedule($artist->id)
            ->filter(function ($schedule) use ($date) {
                return $schedule->day_of_week >= $date->dayOfWeek ||
                       $schedule->day_of_week < $date->copy()->addDays(7)->dayOfWeek;
            })
            ->keyBy('day_of_week');

        // Cache key for appointments
        $cacheKey = "appointments:{$artist->id}:{$date->format('Y-m-d')}";
        
        // Get existing appointments from cache or database
        $existingAppointments = Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($artist, $date) {
                return $artist->appointments()
                    ->select(['id', 'artist_id', 'starts_at', 'ends_at'])
                    ->where('starts_at', '>=', $date->copy()->startOfDay())
                    ->where('starts_at', '<=', $date->copy()->addDays(7)->endOfDay())
                    ->orderBy('starts_at')
                    ->get();
            }
        );

        $availableSlots = collect();
        $currentDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->addDays(7)->endOfDay();

        while ($currentDate->lte($endDate) && $availableSlots->count() < $limit) {
            $daySchedule = $workSchedules->get($currentDate->dayOfWeek);
            
            // Skip if no work schedule for this day
            if (!$daySchedule) {
                $currentDate->addDay();
                continue;
            }

            // Get available slots for this day
            $daySlots = $this->findAvailableSlotsForDay(
                date: $currentDate,
                workStart: Carbon::parse($daySchedule->start_time),
                workEnd: Carbon::parse($daySchedule->end_time),
                appointments: $existingAppointments->filter(
                    fn ($apt) => Carbon::parse($apt->starts_at)->isSameDay($currentDate)
                ),
                duration: $duration,
                buffer: $buffer
            );

            $availableSlots = $availableSlots->concat($daySlots);
            
            if ($availableSlots->count() >= $limit) {
                $availableSlots = $availableSlots->take($limit);
                break;
            }

            $currentDate->addDay();
        }

        return $availableSlots->map(function ($slot) use ($duration) {
            return [
                'starts_at' => $slot->toDateTimeString(),
                'ends_at' => $slot->copy()->addMinutes($duration)->toDateTimeString(),
                'duration' => $duration
            ];
        });
    }

    /**
     * Find available slots for a specific day
     */
    private function findAvailableSlotsForDay(
        Carbon $date,
        Carbon $workStart,
        Carbon $workEnd,
        Collection $appointments,
        int $duration,
        int $buffer = 0
    ): Collection {
        // Set the work hours for this specific date
        $dayStart = $date->copy()->setTimeFrom($workStart);
        $dayEnd = $date->copy()->setTimeFrom($workEnd);

        // If the date is today, start from now
        if ($date->isToday() && now()->gt($dayStart)) {
            $dayStart = now()->ceil(30 * 60); // Round up to next 30 minutes
        }

        // Create 30-minute intervals for the work day
        $intervals = collect(CarbonPeriod::create(
            $dayStart,
            '30 minutes',
            $dayEnd->subMinutes($duration)
        ));

        // Pre-calculate appointment time ranges for better performance
        $appointmentRanges = $appointments->map(function ($appointment) use ($buffer) {
            $start = Carbon::parse($appointment->starts_at);
            $end = Carbon::parse($appointment->ends_at);
            
            if ($buffer > 0) {
                $start = $start->copy()->subMinutes($buffer);
                $end = $end->copy()->addMinutes($buffer);
            }
            
            return [$start, $end];
        });

        return $intervals->filter(function ($startTime) use ($duration, $appointmentRanges) {
            $proposedStart = $startTime->copy();
            $proposedEnd = $startTime->copy()->addMinutes($duration);

            // Check if this slot overlaps with any existing appointments
            foreach ($appointmentRanges as [$appointmentStart, $appointmentEnd]) {
                if ($this->timeslotsOverlap(
                    $proposedStart,
                    $proposedEnd,
                    $appointmentStart,
                    $appointmentEnd
                )) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Check if two time slots overlap
     */
    private function timeslotsOverlap(
        Carbon $start1,
        Carbon $end1,
        Carbon $start2,
        Carbon $end2
    ): bool {
        return $start1 < $end2 && $start2 < $end1;
    }
} 