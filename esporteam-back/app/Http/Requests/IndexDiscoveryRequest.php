<?php

namespace App\Http\Requests;

use App\Enums\SportGoal;
use App\Enums\SportLevel;
use App\Enums\SportSessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexDiscoveryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'mode' => ['nullable', Rule::in(['people', 'sessions', 'places'])],
            'sport_id' => ['nullable', 'integer', Rule::exists('sports', 'id')->where('is_active', true)],
            'sport_slug' => ['nullable', 'string', Rule::exists('sports', 'slug')->where('is_active', true)],
            'type' => ['nullable', Rule::in(SportSessionType::values())],
            'level' => ['nullable', Rule::in(SportLevel::values())],
            'goal' => ['nullable', Rule::in(SportGoal::values())],
            'distance_km' => ['nullable', 'numeric', 'min:1', 'max:200'],
            'weekday' => ['nullable', 'required_with:starts_at,ends_at', 'integer', 'between:0,6'],
            'starts_at' => ['nullable', 'required_with:weekday,ends_at', 'date_format:H:i'],
            'ends_at' => ['nullable', 'required_with:weekday,starts_at', 'date_format:H:i', 'after:starts_at'],
        ];
    }
}
