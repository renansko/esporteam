<?php

namespace App\Http\Requests;

use App\Enums\ProfileVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertSportProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'display_name'     => ['required', 'string', 'max:80'],
            'bio'              => ['nullable', 'string', 'max:1000'],
            'city'             => ['nullable', 'string', 'max:120'],
            'region'           => ['nullable', 'string', 'max:120'],
            'latitude_approx'  => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_approx' => ['nullable', 'numeric', 'between:-180,180'],
            'visibility'       => ['nullable', Rule::in(ProfileVisibility::values())],
            'avatar_url'       => ['nullable', 'url', 'max:2048'],
        ];
    }
}
