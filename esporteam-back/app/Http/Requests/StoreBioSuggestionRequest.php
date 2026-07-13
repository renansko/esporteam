<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBioSuggestionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'instruction' => [
                'nullable',
                'string',
                'max:'.config('bio_assisted.max_instruction_chars', 500),
            ],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
        ];
    }
}
