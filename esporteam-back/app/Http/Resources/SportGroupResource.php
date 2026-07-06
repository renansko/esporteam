<?php

namespace App\Http\Resources;

use App\Models\SportGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SportGroup
 */
class SportGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'creator_profile_id' => $this->creator_profile_id,
            'sport_id' => $this->sport_id,
            'name' => $this->name,
            'description' => $this->description,
            'visibility' => $this->visibility,
            'capacity' => $this->capacity,
            'creator' => $this->whenLoaded('creator', fn () => new SportProfileResource($this->creator)),
            'sport' => $this->whenLoaded('sport', fn () => new SportResource($this->sport)),
            'members' => SportProfileResource::collection($this->whenLoaded('members')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
