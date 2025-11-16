<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeNewsletterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'gdpr_consent' => ['required', 'accepted'],
        ];
    }

    /**
     * Get custom validation messages for the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => __('newsletter.validation.email_required'),
            'email.email' => __('newsletter.validation.email_format'),
            'email.max' => __('newsletter.validation.email_max'),
            'gdpr_consent.required' => __('newsletter.validation.gdpr_required'),
            'gdpr_consent.accepted' => __('newsletter.validation.gdpr_accepted'),
        ];
    }
}
