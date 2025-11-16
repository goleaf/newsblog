<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        $comment = $this->route('comment');

        $userRole = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

        // Admin and editor can delete any comment
        if (in_array($userRole, [\App\Enums\UserRole::Admin->value, \App\Enums\UserRole::Editor->value], true)) {
            return true;
        }

        // Users can delete their own comments
        return $user->id === $comment->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    /**
     * Get custom error messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            //
        ];
    }
}
