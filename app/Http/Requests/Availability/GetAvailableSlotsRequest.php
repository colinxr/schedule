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
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'preferred_time' => ['sometimes', 'string', 'in:morning,afternoon,evening'],
            'emergency' => ['sometimes', 'boolean'],
            'buffer' => ['sometimes', 'integer', 'min:0', 'max:120'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'duration.min' => 'Appointments must be at least 30 minutes long',
            'duration.max' => 'Appointments cannot be longer than 8 hours',
            'date.after_or_equal' => 'Please select today or a future date',
            'per_page.max' => 'Cannot request more than 50 slots per page',
            'page.min' => 'Page number must be at least 1',
            'buffer.max' => 'Buffer time cannot be more than 2 hours',
            'limit.max' => 'Cannot request more than 100 slots at once',
        ];
    }
} 