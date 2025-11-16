<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.custom.contact.name_required'),
            'email.required' => __('validation.custom.contact.email_required'),
            'email.email' => __('validation.custom.contact.email_email'),
            'subject.max' => __('validation.custom.contact.subject_max'),
            'message.required' => __('validation.custom.contact.message_required'),
        ];
    }
}

