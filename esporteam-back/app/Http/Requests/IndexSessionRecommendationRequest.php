<?php

namespace App\Http\Requests;

use App\Enums\SportGoal;
use App\Enums\SportLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSessionRecommendationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'level' => ['nullable', Rule::in(SportLevel::values())],
            'goal' => ['nullable', Rule::in(SportGoal::values())],
            'distance_km' => ['nullable', 'numeric', 'min:1', 'max:200'],
        ];
    }
}
