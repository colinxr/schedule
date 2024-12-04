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
        return true; // Adjust based on your authorization needs
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
            'reference_images.*' => 'image|max:5120', // 5MB max per image
            'email' => 'required|email',
        ];
    }
} 