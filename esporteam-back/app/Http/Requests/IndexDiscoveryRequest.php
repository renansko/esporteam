<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexDiscoveryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'weekday' => ['nullable', 'required_with:starts_at,ends_at', 'integer', 'between:0,6'],
            'starts_at' => ['nullable', 'required_with:weekday,ends_at', 'date_format:H:i'],
            'ends_at' => ['nullable', 'required_with:weekday,starts_at', 'date_format:H:i', 'after:starts_at'],
        ];
    }
}
