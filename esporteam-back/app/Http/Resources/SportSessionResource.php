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
            'rules' => $this->rules,
            'equipment' => $this->equipment,
            'type' => $this->type?->value,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'timezone' => $this->timezone,
            'location_label' => $this->location_label,
            'location_label_public' => $this->location_label_public ?? $this->location_label,
            'city' => $this->city,
            'region' => $this->region,
            'location' => [
                'latitude_approx' => $this->latitude_approx,
                'longitude_approx' => $this->longitude_approx,
            ],
            'meeting_point' => $this->when((bool) $this->getAttribute('exact_location_authorized'), fn () => [
                'label' => $this->meeting_point_label,
                'latitude' => $this->latitude_exact,
                'longitude' => $this->longitude_exact,
            ]),
            'capacity' => $this->when($this->isOwnedByRequester($request), $this->capacity),
            'requires_approval' => $this->requires_approval,
            'entry_mode' => $this->entry_mode?->value,
            'min_level' => $this->min_level,
            'max_level' => $this->max_level,
            'visibility' => $this->visibility,
            'status' => $this->status?->value,
            'version' => $this->version,
            'is_series_override' => $this->is_series_override,
            'change_notice' => $this->change_notice,
            'cancelled_reason' => $this->when($this->isOwnedByRequester($request), $this->cancelled_reason),
            'participant_count' => $this->participant_count ?? $this->participants_count,
            'distance_km' => $this->when($this->getAttribute('distance_km') !== null, $this->getAttribute('distance_km')),
            'next_action' => $this->getAttribute('next_action') ?? 'indisponivel',
            'conversation_unread_count' => $this->when($this->getAttribute('conversation_unread_count') !== null, $this->getAttribute('conversation_unread_count')),
            'series' => $this->when(config('features.recurring_events', false) && $this->relationLoaded('series'), fn () => [
                'id' => $this->series->id,
                'timezone' => $this->series->timezone,
                'interval_weeks' => $this->series->interval_weeks,
                'weekdays' => $this->series->weekdays,
                'ends_type' => $this->series->ends_type,
                'version' => $this->series->version,
                'next_occurrence' => $this->when($this->getAttribute('series_next_occurrence') !== null, fn () => [
                    'id' => $this->getAttribute('series_next_occurrence')->id,
                    'starts_at' => $this->getAttribute('series_next_occurrence')->starts_at?->toISOString(),
                ]),
            ]),
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
