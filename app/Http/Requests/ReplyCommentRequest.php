<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplyCommentRequest extends FormRequest
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
            'parent_id' => ['required', 'exists:comments,id'],
            'author_name' => ['required', 'string', 'max:255'],
            'author_email' => ['required', 'email', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'honeypot' => ['nullable'],
            'page_load_time' => ['nullable', 'numeric'],
        ];
    }

    /**
     * Get custom error messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_id.required' => 'The parent comment ID is required.',
            'parent_id.exists' => 'The parent comment does not exist.',
            'author_name.required' => 'Your name is required.',
            'author_name.string' => 'Your name must be a valid text.',
            'author_name.max' => 'Your name cannot exceed 255 characters.',
            'author_email.required' => 'Your email address is required.',
            'author_email.email' => 'Please provide a valid email address.',
            'author_email.max' => 'Your email address cannot exceed 255 characters.',
            'content.required' => 'Reply content is required.',
            'content.string' => 'Reply content must be valid text.',
            'content.max' => 'Reply content cannot exceed 5000 characters.',
            'page_load_time.numeric' => 'Page load time must be a valid number.',
        ];
    }
}
