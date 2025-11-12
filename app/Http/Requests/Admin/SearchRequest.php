<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:200', 'regex:/^[\p{L}\p{N}\s\-_]+$/u'],
            'type' => ['nullable', 'string', 'in:posts,users,comments,all'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'q.required' => 'The search query is required.',
            'q.max' => 'The search query cannot exceed 200 characters.',
            'q.regex' => 'The search query contains invalid characters.',
            'type.in' => 'The search type must be one of: posts, users, comments, all.',
            'limit.integer' => 'The limit must be an integer.',
            'limit.min' => 'The limit must be at least 1.',
            'limit.max' => 'The limit cannot exceed 100.',
        ];
    }
}
