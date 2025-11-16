<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Requirements: 1.3
     */
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $this->user() && $this->user()->can('update', $article);
    }

    /**
     * Prepare the data for validation.
     * Auto-generate slug from title if not provided.
     * Requirements: 19.4
     */
    protected function prepareForValidation(): void
    {
        if (empty($this->slug) && ! empty($this->title)) {
            $this->merge([
                'slug' => Str::slug($this->title),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     * Requirements: 1.3, 19.4
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $article = $this->route('article');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($article->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'image_alt_text' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published,scheduled'],
            'is_featured' => ['boolean'],
            'is_trending' => ['boolean'],
            'is_breaking' => ['boolean'],
            'is_sponsored' => ['boolean'],
            'is_editors_pick' => ['boolean'],
            'category_id' => ['required', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date', 'required_if:status,scheduled', 'after:now'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     * Requirements: 1.3
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The article title is required.',
            'title.max' => 'The article title must not exceed 255 characters.',
            'content.required' => 'The article content is required.',
            'category_id.required' => 'Please select a category for the article.',
            'category_id.exists' => 'The selected category is invalid.',
            'featured_image.image' => 'The featured image must be an image file.',
            'featured_image.mimes' => 'The featured image must be a JPEG, JPG, PNG, or WebP file.',
            'featured_image.max' => 'The featured image must not exceed 2MB.',
            'meta_title.max' => 'The meta title must not exceed 70 characters for optimal search engine display.',
            'meta_description.max' => 'The meta description must not exceed 160 characters for optimal search engine display.',
            'scheduled_at.required_if' => 'A scheduled date is required when the article status is scheduled.',
            'scheduled_at.after' => 'The scheduled date must be in the future.',
            'tags.*.exists' => 'One or more selected tags are invalid.',
        ];
    }
}
