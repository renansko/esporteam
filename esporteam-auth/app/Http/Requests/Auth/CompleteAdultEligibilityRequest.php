<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CompleteAdultEligibilityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'adult_attestation' => ['accepted'],
        ];
    }
}
