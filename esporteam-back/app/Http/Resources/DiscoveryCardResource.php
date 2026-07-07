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
                'slots' => $this->resource['slots'],
                'host' => new SportProfileResource($this->resource['host']),
                'session' => new SportSessionResource($this->resource['session']),
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
}
