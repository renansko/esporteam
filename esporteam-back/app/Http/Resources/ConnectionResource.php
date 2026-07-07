<?php

namespace App\Http\Resources;

use App\Models\Connection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Connection
 */
class ConnectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requester_profile_id' => $this->requester_profile_id,
            'target_profile_id' => $this->target_profile_id,
            'type' => $this->type,
            'status' => $this->status,
            'safety_actions' => $this->safetyActions($request),
            'requester' => $this->whenLoaded('requester', fn () => new PublicSportProfileResource($this->requester)),
            'target' => $this->whenLoaded('target', fn () => new PublicSportProfileResource($this->target)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function safetyActions(Request $request): array
    {
        $targetProfileId = (int) $this->requester?->user_id === (int) $request->user()?->id
            ? $this->target_profile_id
            : $this->requester_profile_id;

        return [
            'block' => [
                'method' => 'POST',
                'endpoint' => '/api/connections',
                'payload' => [
                    'target_profile_id' => $targetProfileId,
                    'type' => 'block',
                ],
            ],
            'report' => [
                'method' => 'POST',
                'endpoint' => '/api/reports',
                'payload' => [
                    'reported_profile_id' => $targetProfileId,
                ],
            ],
        ];
    }
}
