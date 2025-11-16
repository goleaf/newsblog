<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role->value === 'admin' || $this->user()?->role->value === 'editor';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $categoryId = $this->route('category')->id ?? $this->route('category');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($categoryId)],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('categories', 'slug')->ignore($categoryId), 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'exists:categories,id', function ($attribute, $value, $fail) use ($categoryId) {
                // Prevent setting self as parent
                if ($value == $categoryId) {
                    $fail('A category cannot be its own parent.');
                }

                // Prevent circular references by checking if the parent is a descendant
                if ($value) {
                    $category = \App\Models\Category::find($categoryId);
                    if ($category) {
                        $descendantIds = $category->getAllDescendantIds();
                        if (in_array($value, $descendantIds)) {
                            $fail('Cannot set a descendant category as parent (circular reference).');
                        }
                    }
                }
            }],
            'icon' => ['nullable', 'string', 'max:50'],
            'color_code' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The category name is required.',
            'name.unique' => 'A category with this name already exists.',
            'slug.unique' => 'A category with this slug already exists.',
            'slug.regex' => 'The slug must contain only lowercase letters, numbers, and hyphens.',
            'parent_id.exists' => 'The selected parent category does not exist.',
            'color_code.regex' => 'The color code must be a valid hex color (e.g., #FF5733).',
            'meta_title.max' => 'The meta title should not exceed 60 characters for optimal SEO.',
            'meta_description.max' => 'The meta description should not exceed 160 characters for optimal SEO.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
