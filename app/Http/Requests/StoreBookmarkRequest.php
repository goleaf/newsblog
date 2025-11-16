<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookmarkRequest extends FormRequest
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
        $post = $this->route('post');
        $postId = $post instanceof \App\Models\Post ? $post->id : $post;

        return [
            'post' => [
                'required',
                function ($attribute, $value, $fail) use ($postId) {
                    if (! $postId || ! \App\Models\Post::where('id', $postId)->exists()) {
                        $fail(__('bookmark.post_id_exists'));

                        return;
                    }

                    if (\App\Models\Bookmark::where('user_id', $this->user()?->id)
                        ->where('post_id', $postId)
                        ->exists()) {
                        $fail(__('bookmark.post_id_unique'));
                    }
                },
            ],
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
            'post.required' => __('bookmark.post_id_required'),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'post' => $this->route('post'),
        ]);
    }
}
