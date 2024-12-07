<?php

namespace App\Http\Requests\Availability;

use Illuminate\Foundation\Http\FormRequest;

class GetAvailableSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Anyone can check availability
    }

    public function rules(): array
    {
        return [
            'duration' => ['required', 'integer', 'min:30', 'max:480'], // 30 mins to 8 hours
            'date' => ['sometimes', 'date', 'after:today'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'duration.min' => 'Appointments must be at least 30 minutes long',
            'duration.max' => 'Appointments cannot be longer than 8 hours',
            'date.after' => 'Please select a future date',
        ];
    }
} 