<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Media::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:10240',
            'alt_text' => 'required_if:file_type,image|max:255',
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'alt_text.required_if' => 'Alt text is required for images to ensure accessibility.',
            'file.required' => 'Please select a file to upload.',
            'file.mimes' => 'The file must be a valid image or document type.',
            'file.max' => 'The file size must not exceed 10MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'alt_text' => 'alternative text',
        ];
    }
}
