<?php

namespace App\Http\Requests;

use App\Enums\ClusteringRunStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexClusteringRunsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cursor'        => ['nullable', 'string'],
            'status'        => ['nullable', Rule::in(ClusteringRunStatus::values())],
            'fallback_used' => ['nullable'],
        ];
    }
}
