<?php

namespace App\Http\Requests;

use App\Enums\SportLevel;
use App\Enums\SportSessionEntryMode;
use App\Enums\SportSessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSportSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sport_id' => ['nullable', 'integer', Rule::exists('sports', 'id')->where('is_active', true)],
            'sport_slug' => ['nullable', 'string', Rule::exists('sports', 'slug')->where('is_active', true)],
            'type' => ['nullable', Rule::in(SportSessionType::values())],
            'entry_mode' => ['nullable', Rule::in(SportSessionEntryMode::values())],
            'level' => ['nullable', Rule::in(SportLevel::values())],
            'distance_km' => ['nullable', 'numeric', 'min:1', 'max:200'],
            'weekday' => ['nullable', 'required_with:starts_at,ends_at', 'integer', 'between:0,6'],
            'starts_at' => ['nullable', 'required_with:weekday,ends_at', 'date_format:H:i'],
            'ends_at' => ['nullable', 'required_with:weekday,starts_at', 'date_format:H:i', 'after:starts_at'],
            'has_available_slots' => ['nullable', 'boolean'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'starts_after' => ['nullable', 'date'],
            'starts_before' => ['nullable', 'date', 'after:starts_after'],
        ];
    }
}
