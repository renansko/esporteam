<?php

namespace App\Http\Resources;

use App\Models\AvailabilityWindow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AvailabilityWindow
 */
class AvailabilityWindowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'weekday'   => $this->weekday,
            'starts_at' => substr((string) $this->starts_at, 0, 5),
            'ends_at'   => substr((string) $this->ends_at, 0, 5),
        ];
    }
}
