<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'preferences' => 'required|array',
            'preferences.theme' => 'nullable|in:light,dark,auto',
            'preferences.reading_list_public' => 'nullable|boolean',
            'preferences.profile_visibility' => 'nullable|in:public,private,followers',
            'preferences.show_email' => 'nullable|boolean',
            'preferences.show_location' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preferences.required' => 'Preferences data is required.',
            'preferences.theme.in' => 'Theme must be light, dark, or auto.',
            'preferences.profile_visibility.in' => 'Profile visibility must be public, private, or followers.',
        ];
    }
}
