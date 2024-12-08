<?php

namespace App\Services\Availability;

use Illuminate\Support\Collection;

interface TimePreferenceStrategy
{
    public function filterSlots(Collection $slots): Collection;
} 