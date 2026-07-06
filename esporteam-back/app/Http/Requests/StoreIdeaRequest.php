<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description'  => ['required', 'string', 'max:5000'],
            'title'        => ['nullable', 'string', 'max:255'],
            'author_email' => ['nullable', 'email:rfc'],
        ];
    }
}
