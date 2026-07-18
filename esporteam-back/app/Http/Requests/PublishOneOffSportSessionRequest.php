<?php

namespace App\Http\Requests;

use App\Enums\SportLevel;
use App\Enums\SportSessionEntryMode;
use App\Enums\SportSessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublishOneOffSportSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sport_id' => ['required', 'integer', Rule::exists('sports', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(SportSessionType::values())],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'timezone' => ['required', 'timezone'],
            'meeting_point_label' => ['required', 'string', 'max:160'],
            'location_label_public' => ['required', 'string', 'max:160'],
            'city' => ['required', 'string', 'max:120'],
            'region' => ['required', 'string', 'max:120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'entry_mode' => ['required', Rule::in(SportSessionEntryMode::values())],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'rules' => ['nullable', 'string', 'max:2000'],
            'equipment' => ['nullable', 'string', 'max:1000'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'min_level' => ['nullable', Rule::in(SportLevel::values())],
            'max_level' => ['nullable', Rule::in(SportLevel::values())],
            'price_cents' => ['prohibited'],
            'fee_cents' => ['prohibited'],
            'is_paid' => ['prohibited'],
            'payment_required' => ['prohibited'],
            'currency' => ['prohibited'],
        ];
    }
}
