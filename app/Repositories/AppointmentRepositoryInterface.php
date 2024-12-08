<?php

namespace App\Repositories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

interface AppointmentRepositoryInterface
{
    public function getArtistAppointmentsForDateRange(
        int $artistId, 
        Carbon $startDate, 
        Carbon $endDate
    ): Collection;

    public function getAppointmentsForDate(User $artist, Carbon $date): Collection;
} 