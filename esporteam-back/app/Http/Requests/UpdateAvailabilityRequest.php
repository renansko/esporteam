<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAvailabilityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'windows' => ['required', 'array', 'max:28'],
            'windows.*.weekday' => ['required', 'integer', 'between:0,6'],
            'windows.*.starts_at' => ['required', 'date_format:H:i'],
            'windows.*.ends_at' => ['required', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('windows', []) as $index => $window) {
                $startsAt = $window['starts_at'] ?? null;
                $endsAt = $window['ends_at'] ?? null;

                if (! is_string($startsAt) || ! is_string($endsAt)) {
                    continue;
                }

                if (! preg_match('/^\d{2}:\d{2}$/', $startsAt) || ! preg_match('/^\d{2}:\d{2}$/', $endsAt)) {
                    continue;
                }

                if ($endsAt <= $startsAt) {
                    $validator->errors()->add("windows.$index.ends_at", 'The availability end time must be after the start time.');
                }
            }
        });
    }
}
