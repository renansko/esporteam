<?php

namespace App\Http\Requests;

class UpdateSportSessionSeriesFromOccurrenceRequest extends UpdateSportSessionOccurrenceRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'series_version' => ['required', 'integer', 'min:1'],
            'starts_at_local' => ['sometimes', 'date_format:H:i'],
            'interval_weeks' => ['sometimes', 'integer', 'min:1', 'max:52'],
            'weekdays' => ['sometimes', 'array', 'min:1', 'max:7'],
            'weekdays.*' => ['integer', 'between:1,7', 'distinct'],
            'ends_type' => ['sometimes', 'in:never,date,count'],
            'ends_on' => ['sometimes', 'nullable', 'date'],
            'occurrence_count' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10000'],
        ]);
    }
}
