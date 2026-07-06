<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertTeacherProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:160'],
            'credentials' => ['nullable', 'string', 'max:2000'],
            'hourly_price_cents' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'service_radius_km' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
