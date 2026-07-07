<?php

namespace App\Http\Resources;

use App\Models\SportProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SportProfile
 */
class PublicSportProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'display_name' => $this->display_name,
            'bio' => $this->bio,
            'city' => $this->city,
            'region' => $this->region,
            'location_label' => $this->locationLabel(),
            'visibility' => $this->visibility?->value,
            'avatar_url' => $this->avatar_url,
            'trust_signals' => $this->trustSignals(),
            'safety_actions' => $this->safetyActions($request),
            'sports' => ProfileSportResource::collection($this->whenLoaded('sports')),
            'availability' => AvailabilityWindowResource::collection($this->whenLoaded('availabilityWindows')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function locationLabel(): ?string
    {
        return collect([$this->city, $this->region])
            ->filter(fn (?string $part) => filled($part))
            ->implode(', ') ?: null;
    }

    private function trustSignals(): array
    {
        $hasSports = $this->relationLoaded('sports') && $this->sports->isNotEmpty();
        $hasAvailability = $this->relationLoaded('availabilityWindows') && $this->availabilityWindows->isNotEmpty();
        $hasLocation = filled($this->city) || filled($this->region);
        $hasAvatar = filled($this->avatar_url);
        $hasBio = filled($this->bio);

        return [
            'profile_complete' => $hasSports && $hasAvailability && $hasLocation && $hasAvatar,
            'has_avatar' => $hasAvatar,
            'has_bio' => $hasBio,
            'has_public_location' => $hasLocation,
            'has_sports' => $hasSports,
            'has_availability' => $hasAvailability,
        ];
    }

    private function safetyActions(Request $request): array
    {
        if ((int) $this->user_id === (int) $request->user()?->id) {
            return [];
        }

        return [
            'block' => [
                'method' => 'POST',
                'endpoint' => '/api/connections',
                'payload' => [
                    'target_profile_id' => $this->id,
                    'type' => 'block',
                ],
            ],
            'report' => [
                'method' => 'POST',
                'endpoint' => '/api/reports',
                'payload' => [
                    'reported_profile_id' => $this->id,
                ],
            ],
        ];
    }
}
