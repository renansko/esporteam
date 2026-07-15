<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'     => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
            'permissions'  => ['nullable', 'integer', 'min:0'],
            'invite_token' => ['nullable', 'string', 'size:64'],
            'registration_intent' => ['nullable', 'in:participant,teacher'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'adult_attestation' => ['nullable', 'required_with:birth_date', 'accepted'],
        ];
    }
}
