<?php

namespace App\Http\Requests;

use App\Enums\RoadmapItemOrigin;
use App\Enums\RoadmapItemStatus;
use App\Enums\RoadmapItemVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRoadmapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cursor'     => ['nullable', 'string'],
            'status'     => ['nullable', Rule::in(RoadmapItemStatus::values())],
            'origin'     => ['nullable', Rule::in(RoadmapItemOrigin::values())],
            'visibility' => ['nullable', Rule::in(RoadmapItemVisibility::values())],
        ];
    }
}
