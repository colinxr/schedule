<?php

namespace App\Http\Requests\Availability;

use Illuminate\Foundation\Http\FormRequest;

class GetAvailableSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'duration' => ['required', 'integer', 'min:30', 'max:480'],
            'date' => ['sometimes', 'date', 'after_or_equal:today'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'buffer' => ['sometimes', 'integer', 'min:0', 'max:60'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'preferred_time' => ['sometimes', 'string', 'in:morning,afternoon,evening'],
            'emergency' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'duration.min' => 'Appointments must be at least 30 minutes long',
            'duration.max' => 'Appointments cannot be longer than 8 hours',
            'date.after_or_equal' => 'Please select today or a future date',
            'buffer.max' => 'Buffer time cannot exceed 60 minutes',
        ];
    }
} 