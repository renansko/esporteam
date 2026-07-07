<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Report
 */
class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reporter_profile_id' => $this->reporter_profile_id,
            'reported_profile_id' => $this->reported_profile_id,
            'reason' => $this->reason,
            'details' => $this->details,
            'status' => $this->status,
            'context' => $this->context,
            'reporter' => $this->whenLoaded('reporter', fn () => new PublicSportProfileResource($this->reporter)),
            'reported' => $this->whenLoaded('reported', fn () => new PublicSportProfileResource($this->reported)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
