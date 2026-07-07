<?php

namespace App\Http\Requests;

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
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'starts_after' => ['nullable', 'date'],
            'starts_before' => ['nullable', 'date', 'after:starts_after'],
        ];
    }
}
