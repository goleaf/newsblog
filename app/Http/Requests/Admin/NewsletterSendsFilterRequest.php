<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NewsletterSendsFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user() && ($this->user()->isAdmin() || $this->user()->isEditor()));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'batch_id' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'in:queued,sent,failed'],
        ];
    }
}
