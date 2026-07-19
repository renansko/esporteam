<?php

namespace App\Http\Requests;

use App\Enums\SportLevel;
use App\Enums\SportSessionEntryMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSportSessionOccurrenceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'title' => ['sometimes', 'string', 'max:160'], 'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'rules' => ['sometimes', 'nullable', 'string', 'max:2000'], 'equipment' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'starts_at' => ['sometimes', 'date'], 'ends_at' => ['sometimes', 'date', 'after:starts_at'],
            'timezone' => ['sometimes', 'timezone'], 'meeting_point_label' => ['sometimes', 'string', 'max:160'],
            'location_label_public' => ['sometimes', 'string', 'max:160'], 'city' => ['sometimes', 'string', 'max:120'],
            'region' => ['sometimes', 'string', 'max:120'], 'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'], 'capacity' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10000'],
            'entry_mode' => ['sometimes', Rule::in(SportSessionEntryMode::values())], 'visibility' => ['sometimes', Rule::in(['public', 'private'])],
            'min_level' => ['sometimes', 'nullable', Rule::in(SportLevel::values())], 'max_level' => ['sometimes', 'nullable', Rule::in(SportLevel::values())],
            'price_cents' => ['prohibited'], 'fee_cents' => ['prohibited'], 'is_paid' => ['prohibited'], 'payment_required' => ['prohibited'], 'currency' => ['prohibited'],
        ];
    }
}
