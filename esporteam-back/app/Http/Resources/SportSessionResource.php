<?php

namespace App\Http\Resources;

use App\Models\SportSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SportSession
 */
class SportSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'creator_profile_id' => $this->creator_profile_id,
            'sport_id' => $this->sport_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type?->value,
            'starts_at' => $this->starts_at?->toISOString(),
            'location_label' => $this->location_label,
            'city' => $this->city,
            'region' => $this->region,
            'location' => [
                'latitude_approx' => $this->latitude_approx,
                'longitude_approx' => $this->longitude_approx,
            ],
            'capacity' => $this->capacity,
            'visibility' => $this->visibility,
            'status' => $this->status?->value,
            'participant_count' => $this->participant_count ?? $this->participants_count,
            'creator' => $this->whenLoaded('creator', fn () => new SportProfileResource($this->creator)),
            'sport' => $this->whenLoaded('sport', fn () => new SportResource($this->sport)),
            'participants' => SportProfileResource::collection($this->whenLoaded('participants')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
