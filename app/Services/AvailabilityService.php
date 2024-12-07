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
        int $limit = 5,
        ?int $buffer = 0
    ): Collection {
        // Get the artist's work schedule for the next 7 days
        $workSchedules = $artist->workSchedules()
            ->select(['id', 'user_id', 'day_of_week', 'start_time', 'end_time'])
            ->where(function ($query) use ($date) {
                $query->where('day_of_week', '>=', $date->dayOfWeek)
                    ->orWhere('day_of_week', '<', $date->copy()->addDays(7)->dayOfWeek);
            })
            ->get()
            ->keyBy('day_of_week');

        // Get existing appointments for the next 7 days
        $existingAppointments = $artist->appointments()
            ->select(['id', 'artist_id', 'starts_at', 'ends_at'])
            ->where('starts_at', '>=', $date->copy()->startOfDay())
            ->where('starts_at', '<=', $date->copy()->addDays(7)->endOfDay())
            ->orderBy('starts_at')
            ->get();

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

        return $intervals->filter(function ($startTime) use ($appointments, $duration, $buffer) {
            $proposedStart = $startTime->copy();
            $proposedEnd = $startTime->copy()->addMinutes($duration);

            // Add buffer before and after if specified
            if ($buffer > 0) {
                $proposedStart = $proposedStart->subMinutes($buffer);
                $proposedEnd = $proposedEnd->addMinutes($buffer);
            }

            // Check if this slot overlaps with any existing appointments
            foreach ($appointments as $appointment) {
                $appointmentStart = Carbon::parse($appointment->starts_at);
                $appointmentEnd = Carbon::parse($appointment->ends_at);

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
     * Check if two timeslots overlap
     */
    private function timeslotsOverlap(
        Carbon $start1,
        Carbon $end1,
        Carbon $start2,
        Carbon $end2
    ): bool {
        // Two time slots overlap if one starts during the other
        // or if one completely contains the other
        return ($start1->between($start2, $end2) ||
            $end1->between($start2, $end2) ||
            $start2->between($start1, $end1) ||
            $end2->between($start1, $end1)) ||
            ($start1->equalTo($start2) || $end1->equalTo($end2));
    }
} 