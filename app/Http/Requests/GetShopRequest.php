<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetShopRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'requested_category' => ['nullable', 'string', 'max:255'],
            'requested_meal_type' => ['nullable', 'string', 'max:255'],
            'requested_time_between' => ['nullable', 'string'],
            // 'requested_time_between' => ['nullable', 'date_format:H:i:s'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'requested_time_between.date_format' => 'The time must be in format HH:MM:SS',
            'per_page.max' => 'Maximum per page is 100',
        ];
    }

    /**
     * Get the rate limiting key for this request.
     */
    public function rateLimitKey(): string
    {
        return 'get_shops_' . ($this->user()?->id ?? $this->ip());
    }

    /**
     * Get the rate limit attempts per minute.
     */
    public function rateLimitAttempts(): int
    {
        return 60; // 60 requests per minute
    }
}