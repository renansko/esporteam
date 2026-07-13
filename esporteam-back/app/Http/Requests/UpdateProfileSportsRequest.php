<?php

namespace App\Http\Requests;

use App\Enums\SportGoal;
use App\Enums\SportLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfileSportsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // A profile can be saved before any modality is selected.
            'sports' => ['present', 'array', 'max:20'],
            'sports.*.sport_id' => ['required', 'integer', Rule::exists('sports', 'id')->where('is_active', true), 'distinct'],
            'sports.*.level' => ['required', Rule::in(SportLevel::values())],
            'sports.*.goals' => ['nullable', 'array', 'max:8'],
            'sports.*.goals.*' => ['required', Rule::in(SportGoal::values())],
            'sports.*.preferred_positions' => ['nullable', 'string', 'max:120'],
            'sports.*.is_primary' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $primaryCount = collect($this->input('sports', []))
                ->filter(fn (mixed $sport): bool => is_array($sport) && filter_var($sport['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN))
                ->count();

            if ($primaryCount > 1) {
                $validator->errors()->add('sports', 'Only one sport practice can be primary.');
            }
        });
    }
}
