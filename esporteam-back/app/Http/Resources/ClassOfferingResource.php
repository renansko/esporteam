<?php

namespace App\Http\Resources;

use App\Models\ClassOffering;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClassOffering
 */
class ClassOfferingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher_profile_id' => $this->teacher_profile_id,
            'sport_id' => $this->sport_id,
            'title' => $this->title,
            'description' => $this->description,
            'price_cents' => $this->price_cents,
            'starts_at' => $this->starts_at?->toISOString(),
            'recurrence' => $this->recurrence,
            'location_label' => $this->location_label,
            'city' => $this->city,
            'region' => $this->region,
            'location' => [
                'latitude_approx' => $this->latitude_approx,
                'longitude_approx' => $this->longitude_approx,
            ],
            'distance_km' => $this->distance_km,
            'capacity' => $this->capacity,
            'status' => $this->status?->value,
            'interest_count' => $this->interest_count ?? $this->interests_count,
            'teacher' => $this->whenLoaded('teacher', fn () => new TeacherProfileResource($this->teacher)),
            'sport' => $this->whenLoaded('sport', fn () => new SportResource($this->sport)),
            'interested_profiles' => SportProfileResource::collection($this->whenLoaded('interestedProfiles')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
