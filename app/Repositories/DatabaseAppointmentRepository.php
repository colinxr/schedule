<?php

namespace App\Repositories;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DatabaseAppointmentRepository implements AppointmentRepositoryInterface
{
    public function getArtistAppointmentsForDateRange(
        int $artistId, 
        Carbon $startDate, 
        Carbon $endDate
    ): Collection {
        return Appointment::query()
            ->select(['id', 'artist_id', 'starts_at', 'ends_at'])
            ->where('artist_id', $artistId)
            ->where('starts_at', '>=', $startDate)
            ->where('starts_at', '<=', $endDate)
            ->orderBy('starts_at')
            ->get();
    }

    public function getAppointmentsForDate(User $artist, Carbon $date): Collection
    {
        return Appointment::query()
            ->where('artist_id', $artist->id)
            ->whereDate('starts_at', $date->toDateString())
            ->get();
    }
} 