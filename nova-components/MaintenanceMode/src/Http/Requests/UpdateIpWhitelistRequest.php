<?php

namespace App\MaintenanceMode\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIpWhitelistRequest extends FormRequest
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
            'ips' => ['required', 'array'],
            'ips.*' => ['required', 'ip'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ips.required' => 'The IP addresses field is required.',
            'ips.array' => 'The IP addresses must be an array.',
            'ips.*.required' => 'Each IP address is required.',
            'ips.*.ip' => 'Each IP address must be a valid IP address.',
        ];
    }
}
