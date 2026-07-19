<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexEventConversationRequest extends FormRequest
{
    public function rules(): array
    {
        return ['cursor' => ['nullable', 'integer', 'min:0'], 'limit' => ['nullable', 'integer', 'min:1', 'max:100']];
    }
}
