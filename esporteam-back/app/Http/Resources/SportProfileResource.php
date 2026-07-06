<?php

namespace App\Http\Resources;

use App\Models\SportProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SportProfile
 */
class SportProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'display_name' => $this->display_name,
            'bio'          => $this->bio,
            'city'         => $this->city,
            'region'       => $this->region,
            'location'     => [
                'latitude_approx'  => $this->latitude_approx,
                'longitude_approx' => $this->longitude_approx,
            ],
            'visibility'   => $this->visibility?->value,
            'avatar_url'   => $this->avatar_url,
            'sports'       => ProfileSportResource::collection($this->whenLoaded('sports')),
            'availability' => AvailabilityWindowResource::collection($this->whenLoaded('availabilityWindows')),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
