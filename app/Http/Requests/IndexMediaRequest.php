<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexMediaRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'q' => ['sometimes', 'string', 'max:255'],
            'mime_type' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'page.integer' => __('The page must be an integer.'),
            'page.min' => __('The page must be at least 1.'),
            'per_page.integer' => __('The per page value must be an integer.'),
            'per_page.min' => __('The per page value must be at least 1.'),
            'per_page.max' => __('The per page value may not be greater than 100.'),
            'q.max' => __('The search query may not be greater than 255 characters.'),
            'mime_type.max' => __('The MIME type filter may not be greater than 255 characters.'),
        ];
    }
}
