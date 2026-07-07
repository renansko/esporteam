<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConnectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'target_profile_id' => ['required', 'integer', Rule::exists('sport_profiles', 'id')],
            'type' => ['required', Rule::in(['friendship', 'interest', 'block'])],
        ];
    }
}
