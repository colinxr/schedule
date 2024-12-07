<?php

namespace App\Services\Availability;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class MorningPreference implements TimePreferenceStrategy
{
    public function filterSlots(Collection $slots): Collection
    {
        return $slots->filter(fn ($slot) => 
            Carbon::parse($slot['starts_at'])->hour < 12
        );
    }
} 