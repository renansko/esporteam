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
            'failure_code' => $this->failure_code,
            'prompt_version' => $this->prompt_version,
            'provider' => $this->provider,
            'model' => $this->model,
            'usage' => [
                'tokens_input' => $this->tokens_input,
                'tokens_output' => $this->tokens_output,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
