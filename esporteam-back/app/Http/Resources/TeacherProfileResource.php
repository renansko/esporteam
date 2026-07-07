<?php

namespace App\Http\Resources;

use App\Models\TeacherProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TeacherProfile
 */
class TeacherProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sport_profile_id' => $this->sport_profile_id,
            'headline' => $this->headline,
            'credentials' => $this->credentials,
            'hourly_price_cents' => $this->hourly_price_cents,
            'service_radius_km' => $this->service_radius_km,
            'verified_at' => $this->verified_at?->toISOString(),
            'profile' => $this->whenLoaded('profile', fn () => new PublicSportProfileResource($this->profile)),
            'students' => PublicSportProfileResource::collection($this->whenLoaded('students')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
