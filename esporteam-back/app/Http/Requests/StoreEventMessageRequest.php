<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'client_message_id' => ['required', 'uuid'],
        ];
    }
}
