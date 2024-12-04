<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConversationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'artist_id' => 'required|exists:users,id',
            'description' => 'required|string',
            'reference_images' => 'nullable|array',
            'reference_images.*' => 'image|max:5120',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:conversation_details,email'
            ],
            'phone' => 'nullable|string|max:20',
        ];
    }
} 