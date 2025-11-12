<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
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
        $maxQueryLength = config('fuzzy-search.limits.max_query_length', 200);
        $maxResults = config('fuzzy-search.limits.max_results', 100);

        return [
            'q' => [
                'nullable',
                'string',
                'max:'.$maxQueryLength,
                'regex:/^[\p{L}\p{N}\s\-_]+$/u',
            ],
            'threshold' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:'.$maxResults,
            ],
            'category' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'author' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'date_from' => [
                'nullable',
                'date',
                'before_or_equal:date_to',
            ],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
            'tags' => [
                'nullable',
                'array',
            ],
            'tags.*' => [
                'integer',
                'exists:tags,id',
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
            'q.regex' => 'The search query contains invalid characters. Only letters, numbers, spaces, hyphens, and underscores are allowed.',
            'q.max' => 'The search query cannot exceed :max characters.',
            'threshold.integer' => 'The threshold must be a number.',
            'threshold.min' => 'The threshold must be at least 0.',
            'threshold.max' => 'The threshold cannot exceed 100.',
            'limit.integer' => 'The limit must be a number.',
            'limit.min' => 'The limit must be at least 1.',
            'limit.max' => 'The limit cannot exceed :max.',
            'category.exists' => 'The selected category does not exist.',
            'author.exists' => 'The selected author does not exist.',
            'date_from.date' => 'The date from must be a valid date.',
            'date_from.before_or_equal' => 'The date from must be before or equal to date to.',
            'date_to.date' => 'The date to must be a valid date.',
            'date_to.after_or_equal' => 'The date to must be after or equal to date from.',
            'tags.array' => 'The tags must be an array.',
            'tags.*.exists' => 'One or more selected tags do not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from query
        if ($this->has('q')) {
            $this->merge([
                'q' => trim($this->input('q')),
            ]);
        }
    }
}
