<?php

namespace App\Repositories;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface AppointmentRepository
{
    public function getArtistAppointmentsForDateRange(
        int $artistId, 
        Carbon $startDate, 
        Carbon $endDate
    ): Collection;
}

class EloquentAppointmentRepository implements AppointmentRepository
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
} 