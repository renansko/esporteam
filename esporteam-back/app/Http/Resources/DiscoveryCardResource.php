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
                'vacancy_status' => $this->resource['vacancy_status'],
                'safety_actions' => $this->safetyActions($this->resource['host']),
                'host' => new PublicSportProfileResource($this->resource['host']),
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
            'trust_signals' => $this->resource['trust_signals'],
            'safety_actions' => $this->safetyActions($this->resource['profile']),
            'profile' => new PublicSportProfileResource($this->resource['profile']),
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
            'location_label_public' => $this->locationLabel($session->location_label, $session->city, $session->region),
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
            'approved_participants' => $this->approvedParticipants($session),
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

    private function approvedParticipants($session): array
    {
        if (! $session->relationLoaded('participants')) {
            return [];
        }

        return $session->participants
            ->take(5)
            ->map(fn ($profile) => [
                'id' => $profile->id,
                'display_name' => $profile->display_name,
                'avatar_url' => $profile->avatar_url,
            ])
            ->values()
            ->all();
    }

    private function locationLabel(?string $locationLabel, ?string $city, ?string $region): ?string
    {
        if (filled($locationLabel)) {
            return $locationLabel;
        }

        return collect([$city, $region])
            ->filter(fn (?string $part) => filled($part))
            ->implode(', ') ?: null;
    }

    private function safetyActions($profile): array
    {
        return [
            'block' => [
                'method' => 'POST',
                'endpoint' => '/api/connections',
                'payload' => [
                    'target_profile_id' => $profile->id,
                    'type' => 'block',
                ],
            ],
            'report' => [
                'method' => 'POST',
                'endpoint' => '/api/reports',
                'payload' => [
                    'reported_profile_id' => $profile->id,
                ],
            ],
        ];
    }
}
