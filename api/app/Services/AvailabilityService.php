<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Repositories\AppointmentRepositoryInterface;
use App\Support\TimeslotPaginator;

class AvailabilityService
{
    public function __construct(
        private readonly AppointmentRepositoryInterface $appointmentRepository
    ) {}

    public function findAvailableSlots(
        User $artist,
        int $duration,
        Carbon $date,
        ?int $limit = null,
        bool $lookAhead = false
    ): Collection {
        $availableSlots = collect();

        // Get work schedule for the artist
        $schedule = WorkSchedule::where('user_id', $artist->id)
            ->where('day_of_week', $date->dayOfWeek ?: 7)
            ->where('is_active', true)
            ->first();

        // If no schedule found and looking ahead, try next available day
        if (!$schedule && $lookAhead) {
            for ($i = 1; $i <= 7; $i++) {
                $nextDate = $date->copy()->addDays($i);
                $nextSchedule = WorkSchedule::where('user_id', $artist->id)
                    ->where('day_of_week', $nextDate->dayOfWeek ?: 7)
                    ->where('is_active', true)
                    ->first();

                if ($nextSchedule) {
                    $schedule = $nextSchedule;
                    $date = $nextDate;
                    break;
                }
            }
        }

        // If still no schedule found, return empty collection
        if (!$schedule) {
            return $availableSlots;
        }

        // Get existing appointments for the date
        $existingAppointments = $this->appointmentRepository->getAppointmentsForDate($artist, $date);

        // Initialize current time based on schedule and conditions
        $currentTime = $date->copy();
        if ($lookAhead && $date->isToday()) {
            $currentTime = now()->ceil('30 minutes');
        }
        
        $currentTime->setTimeFromTimeString($schedule->start_time);
        $dayEnd = $date->copy()->setTimeFromTimeString($schedule->end_time);

        // If start time is after end time for today, return empty collection
        if ($currentTime->gt($dayEnd)) {
            return $availableSlots;
        }

        // Iterate through the day in 30-minute increments
        while ($currentTime->copy()->addMinutes($duration)->lte($dayEnd)) {
            $slotEnd = $currentTime->copy()->addMinutes($duration);
            
            // Check if slot overlaps with any existing appointments
            $hasOverlap = $existingAppointments->some(function ($appointment) use ($currentTime, $slotEnd) {
                $appointmentStart = Carbon::parse($appointment->starts_at);
                $appointmentEnd = Carbon::parse($appointment->ends_at);
                
                // A slot overlaps if it starts before an appointment ends 
                // AND ends after an appointment starts
                return $currentTime->lt($appointmentEnd) && 
                       $slotEnd->gt($appointmentStart);
            });

            if (!$hasOverlap) {
                $availableSlots->push([
                    'starts_at' => $currentTime->toDateTimeString(),
                    'ends_at' => $slotEnd->toDateTimeString(),
                    'duration' => $duration
                ]);

                if ($limit && $availableSlots->count() >= $limit) {
                    break;
                }
                
                // Skip ahead by the duration when we find an available slot
                $currentTime->addMinutes($duration);
            } else {
                // Only increment by 30 minutes if the current slot wasn't available
                $currentTime->addMinutes(30);
            }
        }

        return $availableSlots;
    }

    public function getAvailableSlots(
        User $artist,
        ?string $date = null,
        int $duration,
        int $page = 1,
        int $perPage = 10,
        ?int $limit = null
    ): array {
        $date = $date ? now()->parse($date)->startOfDay() : now()->startOfDay();
        
        // Calculate available slots
        $slots = $this->findAvailableSlots(
            $artist,
            $duration,
            $date,
            $limit,
            !$date
        );

        // Convert collection to array and paginate
        return TimeslotPaginator::paginate($slots->toArray(), $page, $perPage);
    }
} 