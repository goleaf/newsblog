<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowCategoryRequest extends FormRequest
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
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'sort' => ['nullable', 'string', 'in:latest,popular,oldest'],
            'date_filter' => ['nullable', 'string', 'in:today,week,month'],
            'page' => ['nullable', 'integer', 'min:1'],
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
            'slug.required' => __('validation.required', ['attribute' => __('category.slug')]),
            'slug.string' => __('validation.string', ['attribute' => __('category.slug')]),
            'slug.max' => __('validation.max.string', ['attribute' => __('category.slug'), 'max' => 255]),
            'slug.regex' => __('validation.regex', ['attribute' => __('category.slug')]),
            'sort.in' => __('validation.in', ['attribute' => __('category.sort')]),
            'date_filter.in' => __('validation.in', ['attribute' => __('category.date_filter')]),
            'page.integer' => __('validation.integer', ['attribute' => __('category.page')]),
            'page.min' => __('validation.min.numeric', ['attribute' => __('category.page'), 'min' => 1]),
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Get slug from route parameter and add to request for validation
        $slug = $this->route('slug');
        if ($slug) {
            $this->merge([
                'slug' => $slug,
            ]);
        }
    }

    /**
     * Get validated slug from route parameter.
     */
    public function getSlug(): string
    {
        return $this->validated()['slug'];
    }
}
