<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelSportSessionOccurrenceRequest extends FormRequest
{
    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:1'], 'reason' => ['nullable', 'string', 'max:500']];
    }
}
