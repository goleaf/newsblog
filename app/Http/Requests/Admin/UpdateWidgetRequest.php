<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewNova') ?? true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'settings' => ['sometimes', 'array'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.string' => __('Title must be a string.'),
        ];
    }
}
