<?php

namespace App\Http\Requests\Admin\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class ShowCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'min:1970', 'max:2100'],
            'author' => ['nullable', 'integer', 'exists:users,id'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'month.integer' => __('validation.calendar_month_integer'),
            'month.between' => __('validation.calendar_month_between'),
            'year.integer' => __('validation.calendar_year_integer'),
            'year.min' => __('validation.calendar_year_min'),
            'year.max' => __('validation.calendar_year_max'),
            // Default messages for author/category are sufficient; custom keys optional
        ];
    }
}
