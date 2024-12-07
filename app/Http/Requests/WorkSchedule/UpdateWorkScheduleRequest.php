<?php

namespace App\Http\Requests\WorkSchedule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'artist' && 
               $this->workSchedule->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
} 