<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexClassOfferingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sport_id' => ['nullable', 'integer', Rule::exists('sports', 'id')->where('is_active', true)],
            'sport_slug' => ['nullable', 'string', Rule::exists('sports', 'slug')->where('is_active', true)],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'min_price_cents' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'max_price_cents' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'distance_km' => ['nullable', 'numeric', 'min:1', 'max:200'],
            'starts_after' => ['nullable', 'date'],
            'starts_before' => ['nullable', 'date', 'after:starts_after'],
        ];
    }
}
