<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reported_profile_id' => ['required', 'integer', Rule::exists('sport_profiles', 'id')],
            'reason' => ['required', 'string', 'max:80'],
            'details' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
