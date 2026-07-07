<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostMatchSportActionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'context' => $this->resource['context'],
            'next_actions' => $this->resource['next_actions'],
            'time_suggestions' => $this->resource['time_suggestions'],
            'location_suggestions' => $this->resource['location_suggestions'],
            'reasons' => $this->resource['reasons'],
        ];
    }
}
