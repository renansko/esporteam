<?php

namespace App\Http\Resources;

use App\Models\ProfileSport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProfileSport
 */
class ProfileSportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sport'               => new SportResource($this->whenLoaded('sport')),
            'level'               => $this->level?->value,
            'goals'               => $this->goals ?? [],
            'preferred_positions' => $this->preferred_positions,
            'is_primary'          => $this->is_primary,
        ];
    }
}
