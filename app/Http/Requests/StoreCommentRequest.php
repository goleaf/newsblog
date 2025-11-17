<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'post_id' => ['sometimes', 'required', 'exists:posts,id'],
            'author_name' => ['required', 'string', 'max:255'],
            'author_email' => ['required', 'email', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
            'honeypot' => ['nullable'],
            'page_load_time' => ['nullable', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'post_id.required' => 'The post ID is required.',
            'post_id.exists' => 'The selected post does not exist.',
            'author_name.required' => 'Your name is required.',
            'author_name.string' => 'Your name must be a valid text.',
            'author_name.max' => 'Your name cannot exceed 255 characters.',
            'author_email.required' => 'Your email address is required.',
            'author_email.email' => 'Please provide a valid email address.',
            'author_email.max' => 'Your email address cannot exceed 255 characters.',
            'content.required' => 'Comment content is required.',
            'content.string' => 'Comment content must be valid text.',
            'content.max' => 'Comment content cannot exceed 5000 characters.',
            'parent_id.exists' => 'The parent comment does not exist.',
            'page_load_time.numeric' => 'Page load time must be a valid number.',
        ];
    }
}
