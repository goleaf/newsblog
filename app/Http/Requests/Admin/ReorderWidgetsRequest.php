<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderWidgetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewNova') ?? true;
    }

    public function rules(): array
    {
        return [
            'widgets' => ['required', 'array'],
            'widgets.*.id' => ['required', 'exists:widgets,id'],
            'widgets.*.order' => ['required', 'integer'],
            'widgets.*.widget_area_id' => ['required', 'exists:widget_areas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'widgets.required' => __('Widgets payload is required.'),
        ];
    }
}
