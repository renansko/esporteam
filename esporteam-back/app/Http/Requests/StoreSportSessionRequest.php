<?php

namespace App\Http\Requests;

use App\Enums\SportLevel;
use App\Enums\SportSessionEntryMode;
use App\Enums\SportSessionStatus;
use App\Enums\SportSessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSportSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sport_id' => ['nullable', 'integer', Rule::exists('sports', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(SportSessionType::values())],
            'starts_at' => ['required', 'date'],
            'location_label' => ['nullable', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'latitude_approx' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_approx' => ['nullable', 'numeric', 'between:-180,180'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'entry_mode' => ['nullable', Rule::in(SportSessionEntryMode::values())],
            'min_level' => ['nullable', Rule::in(SportLevel::values())],
            'max_level' => ['nullable', Rule::in(SportLevel::values())],
            'requires_approval' => ['nullable', 'boolean'],
            'visibility' => ['nullable', Rule::in(['public', 'private'])],
            'status' => ['nullable', Rule::in(SportSessionStatus::values())],
            'price_cents' => ['prohibited'],
            'fee_cents' => ['prohibited'],
            'is_paid' => ['prohibited'],
            'payment_required' => ['prohibited'],
            'currency' => ['prohibited'],
        ];
    }
}
