<?php

namespace App\Services;

use App\Models\User;
use App\Models\Appointment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Find available appointment slots for an artist
     */
    public function findAvailableSlots(
        User $artist,
        int $duration,
        Carbon $date,
        int $limit = 5
    ): Collection {
        // Get the artist's work schedule for the next 7 days
        $workSchedules = $artist->workSchedules()
            ->where('day_of_week', '>=', $date->dayOfWeek)
            ->orWhere('day_of_week', '<', $date->copy()->addDays(7)->dayOfWeek)
            ->get()
            ->keyBy('day_of_week');

        // Get existing appointments for the next 7 days
        $existingAppointments = $artist->appointments()
            ->where('starts_at', '>=', $date->startOfDay())
            ->where('starts_at', '<=', $date->copy()->addDays(7)->endOfDay())
            ->orderBy('starts_at')
            ->get();

        $availableSlots = collect();
        $currentDate = $date->copy();
        $endDate = $date->copy()->addDays(7);

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
                duration: $duration
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
        int $duration
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

        return $intervals->filter(function ($startTime) use ($appointments, $duration) {
            $endTime = $startTime->copy()->addMinutes($duration);

            // Check if this slot overlaps with any existing appointments
            $hasConflict = $appointments->some(function ($appointment) use ($startTime, $endTime) {
                $appointmentStart = Carbon::parse($appointment->starts_at);
                $appointmentEnd = Carbon::parse($appointment->ends_at);

                return $startTime->between($appointmentStart, $appointmentEnd) ||
                    $endTime->between($appointmentStart, $appointmentEnd) ||
                    ($startTime->lte($appointmentStart) && $endTime->gte($appointmentEnd));
            });

            return !$hasConflict;
        });
    }
} 