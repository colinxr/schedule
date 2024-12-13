<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'artist' && 
               $this->user()->id === $this->route('appointment')->artist_id;
    }

    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }
} 