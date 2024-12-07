<?php

namespace App\Http\Requests\WorkSchedule;

use App\Rules\UniqueWorkScheduleDays;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'artist';
    }

    public function rules(): array
    {
        return [
            'schedules' => ['required', 'array', new UniqueWorkScheduleDays],
            'schedules.*.day_of_week' => [
                'required', 
                'integer', 
                'between:0,6',
            ],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.timezone' => ['sometimes', 'string', 'timezone'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Delete any existing schedules for the days being set
        if ($this->has('schedules')) {
            $days = collect($this->schedules)->pluck('day_of_week');
            $this->user()->workSchedules()->whereIn('day_of_week', $days)->delete();
        }
    }
} 