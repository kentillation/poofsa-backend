<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetPublicShopsRequest extends FormRequest
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
            'requested_category'     => 'nullable|string',
            'requested_meal_type'     => 'nullable|string',
            'requested_time_between'     => 'nullable|string',
            'items_per_page'  => 'nullable|integer|min:1',
            'search'        => 'nullable|string',
        ];
    }
}
