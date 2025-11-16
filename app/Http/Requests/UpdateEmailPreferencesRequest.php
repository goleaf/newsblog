<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailPreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'preferences' => 'required|array',
            'preferences.comment_replies' => 'nullable|boolean',
            'preferences.post_published' => 'nullable|boolean',
            'preferences.comment_approved' => 'nullable|boolean',
            'preferences.series_updated' => 'nullable|boolean',
            'preferences.newsletter' => 'nullable|boolean',
            'preferences.frequency' => 'required|in:immediate,daily,weekly',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preferences.required' => __('validation.required', ['attribute' => 'preferences']),
            'preferences.frequency.required' => __('validation.required', ['attribute' => 'frequency']),
            'preferences.frequency.in' => __('validation.in', ['attribute' => 'frequency']),
        ];
    }
}
