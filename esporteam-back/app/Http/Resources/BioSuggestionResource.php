<?php

namespace App\Http\Resources;

use App\Models\BioSuggestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BioSuggestion
 */
class BioSuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bio' => $this->generated_bio,
            'key_points' => $this->structured_output['key_points'] ?? [],
            'status' => $this->status?->value,
            'failure_code' => $this->when($this->failure_code !== null, $this->failure_code),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
