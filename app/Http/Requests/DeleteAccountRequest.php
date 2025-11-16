<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
            'confirm_deletion' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => __('validation.required', ['attribute' => 'password']),
            'password.current_password' => __('The provided password is incorrect.'),
            'confirm_deletion.required' => __('Please confirm account deletion.'),
            'confirm_deletion.accepted' => __('You must confirm to proceed with account deletion.'),
        ];
    }
}
