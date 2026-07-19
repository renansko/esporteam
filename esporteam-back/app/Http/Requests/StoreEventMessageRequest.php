<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:2000', 'required_without:media_ids'],
            'client_message_id' => ['required', 'uuid'],
            'media_ids' => ['nullable', 'array', 'max:4'],
            'media_ids.*' => ['integer', 'distinct'],
        ];
    }
}
