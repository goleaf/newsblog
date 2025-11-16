<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchMediaRequest extends FormRequest
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
            'q' => ['required', 'string', 'max:255'],
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
            'q.required' => __('A search term is required.'),
            'q.max' => __('The search term may not be greater than 255 characters.'),
            'mime_type.max' => __('The MIME type filter may not be greater than 255 characters.'),
        ];
    }
}
