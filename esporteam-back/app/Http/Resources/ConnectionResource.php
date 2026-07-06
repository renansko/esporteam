<?php

namespace App\Http\Resources;

use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Connection
 */
class ConnectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requester_profile_id' => $this->requester_profile_id,
            'target_profile_id' => $this->target_profile_id,
            'type' => $this->type,
            'status' => $this->status,
            'requester' => $this->whenLoaded('requester', fn () => new SportProfileResource($this->requester)),
            'target' => $this->whenLoaded('target', fn () => new SportProfileResource($this->target)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
