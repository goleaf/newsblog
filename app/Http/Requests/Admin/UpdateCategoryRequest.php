<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin() || $this->user()->isEditor();
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')->id ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($categoryId)],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id', Rule::notIn([$categoryId])],
            'icon' => ['nullable', 'string', 'max:255'],
            'color_code' => ['nullable', 'string', 'max:7'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The category name is required.',
            'status.required' => 'Please select a status.',
            'status.in' => 'The selected status is invalid.',
            'slug.unique' => 'This slug is already taken.',
            'parent_id.exists' => 'The selected parent category is invalid.',
            'parent_id.not_in' => 'A category cannot be its own parent.',
        ];
    }
}

