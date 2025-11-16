<?php

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable'],
            'group' => ['required', 'string', 'in:'.implode(',', array_keys(Setting::GROUPS))],
        ];
    }

    public function messages(): array
    {
        return [
            'settings.required' => __('validation.settings_required'),
            'settings.array' => __('validation.settings_array'),
            'group.required' => __('validation.group_required'),
            'group.in' => __('validation.group_invalid'),
        ];
    }
}
