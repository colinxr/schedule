<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class UniqueWorkScheduleDays implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $days = Arr::pluck($value, 'day_of_week');
        
        if (count($days) !== count(array_unique($days))) {
            $fail('You cannot set multiple schedules for the same day.');
        }
    }
} 