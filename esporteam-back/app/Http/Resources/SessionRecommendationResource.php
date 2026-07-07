<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionRecommendationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'score' => $this->resource['score'],
            'reasons' => $this->resource['reasons'],
            'distance_km' => $this->resource['distance_km'],
            'profile' => new SportProfileResource($this->resource['profile']),
        ];
    }
}
