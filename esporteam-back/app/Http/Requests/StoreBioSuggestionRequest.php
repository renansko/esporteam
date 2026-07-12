<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBioSuggestionRequest extends FormRequest
{
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
        ];
    }
}
