<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSportGroupMemberRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sport_profile_id' => ['required', 'integer', Rule::exists('sport_profiles', 'id')],
            'role' => ['nullable', Rule::in(['admin', 'member'])],
            'status' => ['nullable', Rule::in(['active', 'invited'])],
        ];
    }
}
