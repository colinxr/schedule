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
            'duration' => ['required', 'integer', 'min:30', 'max:480', 'multiple_of:30'],
            'date' => ['sometimes', 'date'],
            'limit' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.max' => 'Cannot request more than 50 slots per page',
            'page.min' => 'Page number must be at least 1',
            'per_page.min' => 'Per page must be at least 1',
            'duration.min' => 'Duration must be at least 30 minutes',
            'duration.multiple_of' => 'Duration must be in 30-minute increments',
        ];
    }
} 