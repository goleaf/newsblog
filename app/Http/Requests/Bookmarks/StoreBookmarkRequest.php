<?php

namespace App\Http\Requests\Bookmarks;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookmarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'post_id' => ['required', 'integer', 'exists:posts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'post_id.required' => __('validation.required', ['attribute' => __('post')]),
            'post_id.exists' => __('validation.exists', ['attribute' => __('post')]),
        ];
    }
}



