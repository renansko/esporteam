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
            'capacity' => $this->when($this->isOwnedByRequester($request), $this->capacity),
            'requires_approval' => $this->requires_approval,
            'entry_mode' => $this->entry_mode?->value,
            'min_level' => $this->min_level,
            'max_level' => $this->max_level,
            'visibility' => $this->visibility,
            'status' => $this->status?->value,
            'participant_count' => $this->participant_count ?? $this->participants_count,
            'distance_km' => $this->when($this->getAttribute('distance_km') !== null, $this->getAttribute('distance_km')),
            'next_action' => $this->getAttribute('next_action') ?? 'indisponivel',
            'safety_actions' => $this->whenLoaded('creator', fn () => $this->safetyActions($request)),
            'creator' => $this->whenLoaded('creator', fn () => new PublicSportProfileResource($this->creator)),
            'sport' => $this->whenLoaded('sport', fn () => new SportResource($this->sport)),
            'participation' => SessionParticipantResource::collection($this->whenLoaded('participationRecords')),
            'participants' => PublicSportProfileResource::collection($this->whenLoaded('participants')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function isOwnedByRequester(Request $request): bool
    {
        if (! $this->relationLoaded('creator') || $this->creator === null) {
            return false;
        }

        return (int) $this->creator->user_id === (int) $request->user()?->id;
    }

    private function safetyActions(Request $request): array
    {
        if (! $this->relationLoaded('creator') || $this->creator === null || $this->isOwnedByRequester($request)) {
            return [];
        }

        return [
            'block_host' => [
                'method' => 'POST',
                'endpoint' => '/api/connections',
                'payload' => [
                    'target_profile_id' => $this->creator_profile_id,
                    'type' => 'block',
                ],
            ],
            'report_host' => [
                'method' => 'POST',
                'endpoint' => '/api/reports',
                'payload' => [
                    'reported_profile_id' => $this->creator_profile_id,
                ],
            ],
        ];
    }
}
