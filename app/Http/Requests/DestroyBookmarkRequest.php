<?php

namespace App\Http\Requests;

use App\Models\Bookmark;
use Illuminate\Foundation\Http\FormRequest;

class DestroyBookmarkRequest extends FormRequest
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

        $post = $this->route('post');
        $postId = $post instanceof \App\Models\Post ? $post->id : $post;

        $bookmark = Bookmark::where('user_id', $user->id)
            ->where('post_id', $postId)
            ->first();

        if (! $bookmark) {
            return false;
        }

        return $bookmark->user_id === $user->id;
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
