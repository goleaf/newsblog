<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackEngagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'time_on_page' => ['nullable', 'integer', 'min:0'],
            'scroll_depth' => ['nullable', 'integer', 'min:0', 'max:100'],
            'clicked_bookmark' => ['nullable', 'boolean'],
            'clicked_share' => ['nullable', 'boolean'],
            'clicked_reaction' => ['nullable', 'boolean'],
            'clicked_comment' => ['nullable', 'boolean'],
            'clicked_related_post' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'post_id.required' => __('validation.post_id.required'),
            'post_id.integer' => __('validation.post_id.integer'),
            'post_id.exists' => __('validation.post_id.exists'),
            'time_on_page.integer' => __('validation.time_on_page.integer'),
            'time_on_page.min' => __('validation.time_on_page.min'),
            'scroll_depth.integer' => __('validation.scroll_depth.integer'),
            'scroll_depth.min' => __('validation.scroll_depth.min'),
            'scroll_depth.max' => __('validation.scroll_depth.max'),
        ];
    }
}


