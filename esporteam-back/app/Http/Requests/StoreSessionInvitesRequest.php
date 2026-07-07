<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSessionInvitesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'profile_ids' => ['required', 'array', 'min:1', 'max:50'],
            'profile_ids.*' => ['integer', 'distinct', Rule::exists('sport_profiles', 'id')],
            'price_cents' => ['prohibited'],
            'fee_cents' => ['prohibited'],
            'is_paid' => ['prohibited'],
            'payment_required' => ['prohibited'],
            'currency' => ['prohibited'],
        ];
    }
}
