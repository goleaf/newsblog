<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEditorsPicksOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order' => ['required', 'array', 'min:1', 'max:50'],
            'order.*' => ['integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'order.required' => __('The order list is required.'),
            'order.array' => __('The order must be an array of post IDs.'),
            'order.*.integer' => __('Each item in the order must be a valid ID.'),
            'order.*.distinct' => __('Duplicate posts are not allowed in the order.'),
        ];
    }
}


