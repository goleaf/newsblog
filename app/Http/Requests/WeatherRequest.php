<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WeatherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lon' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.numeric' => __('validation.numeric'),
            'lat.between' => __('validation.between.numeric'),
            'lon.numeric' => __('validation.numeric'),
            'lon.between' => __('validation.between.numeric'),
        ];
    }
}


