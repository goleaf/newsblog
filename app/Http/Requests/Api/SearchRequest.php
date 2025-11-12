<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public API endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'q' => ['required', 'string', 'max:200', 'regex:/^[\p{L}\p{N}\s\-_]+$/u'],
            'threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'category' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];

        // For suggestions endpoint, q is required but can be shorter
        if ($this->routeIs('api.search.suggestions')) {
            $rules['q'] = ['required', 'string', 'min:3', 'max:200', 'regex:/^[\p{L}\p{N}\s\-_]+$/u'];
            $rules['limit'] = ['nullable', 'integer', 'min:1', 'max:10'];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'q.required' => 'The search query is required.',
            'q.max' => 'The search query cannot exceed 200 characters.',
            'q.min' => 'The search query must be at least 3 characters for suggestions.',
            'q.regex' => 'The search query contains invalid characters.',
            'threshold.integer' => 'The threshold must be an integer.',
            'threshold.min' => 'The threshold must be at least 0.',
            'threshold.max' => 'The threshold cannot exceed 100.',
            'limit.integer' => 'The limit must be an integer.',
            'limit.min' => 'The limit must be at least 1.',
            'limit.max' => 'The limit cannot exceed 100.',
            'date_to.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
