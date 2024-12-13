<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'artist' && 
               $this->user()->id === $this->route('appointment')->artist_id;
    }

    public function rules(): array
    {
        $appointment = $this->route('appointment');
        
        if (is_null($appointment->price)) {
            return [
                'deposit_amount' => ['prohibited']
            ];
        }

        return [
            'deposit_amount' => [
                'required',
                'numeric',
                'min:0',
                "max:{$appointment->price}",
                'decimal:0,2'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'deposit_amount.max' => 'The deposit amount cannot exceed the appointment price.',
            'deposit_amount.prohibited' => 'Cannot set deposit amount without a price.'
        ];
    }
} 