<?php

namespace App\Http\Requests\Bookmarks;

use App\Models\Bookmark;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBookmarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Optional when using route model binding; kept for backwards compatibility
            'post_id' => ['nullable', 'integer', 'exists:posts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'post_id.required' => __('validation.required', ['attribute' => __('post')]),
            'post_id.exists' => __('validation.exists', ['attribute' => __('post')]),
        ];
    }

    /**
     * Add an after-validation hook to prevent duplicates.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $user = $this->user();
            if (! $user) {
                return;
            }

            $postId = (int) ($this->route('post')?->id ?? $this->input('post_id'));
            if ($postId === 0) {
                return;
            }

            $exists = Bookmark::query()
                ->where('user_id', $user->id)
                ->where('post_id', $postId)
                ->exists();

            if ($exists) {
                $v->errors()->add('post', __('This post is already bookmarked.'));
            }
        });
    }
}
