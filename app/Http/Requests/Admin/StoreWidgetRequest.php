<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewNova') ?? true;
    }

    public function rules(): array
    {
        return [
            'widget_area_id' => ['required', 'exists:widget_areas,id'],
            'type' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'widget_area_id.required' => __('Widget area is required.'),
            'widget_area_id.exists' => __('Selected widget area does not exist.'),
            'type.required' => __('Widget type is required.'),
            'title.required' => __('Widget title is required.'),
        ];
    }
}
