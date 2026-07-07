<?php

namespace App\Http\Requests;

use App\Enums\ClassOfferingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassOfferingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sport_id' => ['required', 'integer', Rule::exists('sports', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price_cents' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'starts_at' => ['required', 'date'],
            'recurrence' => ['nullable', 'string', 'max:80'],
            'location_label' => ['nullable', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'latitude_approx' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_approx' => ['nullable', 'numeric', 'between:-180,180'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'status' => ['nullable', Rule::in(ClassOfferingStatus::values())],
        ];
    }
}
