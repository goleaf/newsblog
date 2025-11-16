<?php

namespace App\Http\Requests\Admin\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => __('validation.calendar_date_required'),
            'date.date' => __('validation.calendar_date_invalid'),
        ];
    }
}


