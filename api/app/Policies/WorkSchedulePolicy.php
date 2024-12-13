<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkSchedule;

class WorkSchedulePolicy
{
    public function delete(User $user, WorkSchedule $workSchedule): bool
    {
        return $user->id === $workSchedule->user_id && $user->role === 'artist';
    }
} 