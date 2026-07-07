<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscoveryCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource['type'] === 'session') {
            return [
                'type' => 'session',
                'score' => $this->resource['score'],
                'reasons' => $this->resource['reasons'],
                'distance_km' => $this->resource['distance_km'],
                'recommendation_reason' => $this->resource['recommendation_reason'],
                'entry_rule' => $this->resource['entry_rule'],
                'participant_count' => $this->resource['participant_count'],
                'host' => new SportProfileResource($this->resource['host']),
                'session' => $this->sessionSummary($this->resource['session'], $this->resource['participant_count']),
            ];
        }

        if ($this->resource['type'] === 'place') {
            return [
                'type' => 'place',
                'score' => $this->resource['score'],
                'reasons' => $this->resource['reasons'],
                'distance_km' => $this->resource['distance_km'],
                'recommendation_reason' => $this->resource['recommendation_reason'],
                'place' => $this->resource['place'],
            ];
        }

        return [
            'type' => $this->resource['type'],
            'score' => $this->resource['score'],
            'reasons' => $this->resource['reasons'],
            'distance_km' => $this->resource['distance_km'],
            'recommendation_reason' => $this->resource['recommendation_reason'],
            'primary_sport' => $this->resource['primary_sport'],
            'availability_summary' => $this->resource['availability_summary'],
            'location_label' => $this->resource['location_label'],
            'profile' => new SportProfileResource($this->resource['profile']),
            'teacher_profile' => $this->when(
                $this->resource['teacher_profile'] !== null,
                fn () => new TeacherProfileResource($this->resource['teacher_profile']),
            ),
        ];
    }

    private function sessionSummary($session, int $participantCount): array
    {
        return [
            'id' => $session->id,
            'creator_profile_id' => $session->creator_profile_id,
            'sport_id' => $session->sport_id,
            'title' => $session->title,
            'description' => $session->description,
            'type' => $session->type?->value,
            'starts_at' => $session->starts_at?->toISOString(),
            'location_label' => $session->location_label,
            'city' => $session->city,
            'region' => $session->region,
            'location' => [
                'latitude_approx' => $session->latitude_approx,
                'longitude_approx' => $session->longitude_approx,
            ],
            'requires_approval' => $session->requires_approval,
            'entry_mode' => $session->entry_mode?->value,
            'min_level' => $session->min_level,
            'max_level' => $session->max_level,
            'visibility' => $session->visibility,
            'status' => $session->status?->value,
            'participant_count' => $participantCount,
            'sport' => $session->relationLoaded('sport') && $session->sport !== null
                ? $this->sportSummary($session->sport)
                : null,
        ];
    }

    private function sportSummary($sport): array
    {
        return [
            'id' => $sport->id,
            'name' => $sport->name,
            'slug' => $sport->slug,
            'category' => $sport->category,
            'is_active' => $sport->is_active,
        ];
    }
}
