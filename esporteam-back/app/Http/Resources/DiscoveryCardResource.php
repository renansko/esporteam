<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscoveryCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->resource['type'],
            'score' => $this->resource['score'],
            'reasons' => $this->resource['reasons'],
            'distance_km' => $this->resource['distance_km'],
            'profile' => new SportProfileResource($this->resource['profile']),
            'teacher_profile' => $this->when(
                $this->resource['teacher_profile'] !== null,
                fn () => new TeacherProfileResource($this->resource['teacher_profile']),
            ),
        ];
    }
}
