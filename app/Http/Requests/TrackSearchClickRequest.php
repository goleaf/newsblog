<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackSearchClickRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search_log_id' => ['required', 'integer', 'exists:search_logs,id'],
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'position' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'search_log_id.required' => __('validation.search_log_id.required'),
            'search_log_id.integer' => __('validation.search_log_id.integer'),
            'search_log_id.exists' => __('validation.search_log_id.exists'),
            'post_id.required' => __('validation.post_id.required'),
            'post_id.integer' => __('validation.post_id.integer'),
            'post_id.exists' => __('validation.post_id.exists'),
            'position.required' => __('validation.position.required'),
            'position.integer' => __('validation.position.integer'),
            'position.min' => __('validation.position.min'),
        ];
    }
}


