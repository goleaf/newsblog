<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimetypes:image/jpeg,image/jpg,image/png,image/gif,image/webp', 'max:10240'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
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
            'file.required' => __('The media file is required.'),
            'file.file' => __('The uploaded media must be a valid file.'),
            'file.mimetypes' => __('Only JPEG, PNG, GIF, and WebP image formats are allowed.'),
            'file.max' => __('The media file may not be greater than 10MB.'),
            'user_id.required' => __('The owner of the media is required.'),
            'user_id.exists' => __('The selected media owner is invalid.'),
        ];
    }
}
