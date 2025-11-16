<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'symbols' => ['required', 'string', 'regex:/^[A-Za-z0-9,\\.-]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'symbols.required' => __('validation.required'),
            'symbols.string' => __('validation.string'),
            'symbols.regex' => __('validation.regex'),
        ];
    }
}


