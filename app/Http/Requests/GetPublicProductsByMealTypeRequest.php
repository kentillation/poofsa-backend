<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetPublicProductsByMealTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // from false
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'meal_type' => 'required|string|max:255',
            'items_per_page' => 'nullable|integer|min:1|max:100',
            'search'        => 'nullable|string',
        ];
    }
}

// This request is for fetching all public products based on meal type with pagination and search functionality
