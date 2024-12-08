<?php

namespace App\Services\Availability;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AppointmentCache
{
    /**
     * Get cached appointments for an artist on a specific date
     */
    public function getArtistAppointments(int $artistId, Carbon $date): Collection
    {
        $cacheKey = "appointments:{$artistId}:{$date->format('Y-m-d')}";
        
        return Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            fn () => User::find($artistId)
                ->appointments()
                ->select(['id', 'artist_id', 'starts_at', 'ends_at'])
                ->where('starts_at', '>=', $date->copy()->startOfDay())
                ->where('starts_at', '<=', $date->copy()->endOfDay())
                ->orderBy('starts_at')
                ->get()
        );
    }

    /**
     * Clear cached appointments for an artist
     */
    public function clearArtistAppointments(int $artistId): void
    {
        $pattern = "appointments:{$artistId}:*";
        $keys = Cache::get($pattern) ?? [];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
} 