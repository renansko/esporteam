<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSportGroupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sport_id' => ['nullable', 'integer', Rule::exists('sports', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['nullable', Rule::in(['public', 'private'])],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
