<?php

namespace App\Http\Resources;

use App\Models\RoadmapItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @wiki app/brain/resources/RoadmapItemResource.md
 *
 * @mixin RoadmapItem
 */
class RoadmapItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $payload = [
            'id'              => $this->id,
            'title'           => $this->title,
            'description'     => $this->description,
            'status'          => $this->status?->value,
            'visibility'      => $this->visibility?->value,
            'origin'          => $this->origin?->value,
            'score'           => (float) $this->score,
            'score_breakdown' => $this->score_breakdown,
            'votes_count'     => (int) $this->votes_count,
            'ideas_count'     => (int) ($this->ideas_count ?? $this->ideas()->count()),
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];

        if ($this->relationLoaded('ideas')) {
            $payload['ideas'] = $this->ideas->map(function ($idea) {
                $decision = $idea->relationLoaded('clusterDecision')
                    ? $idea->clusterDecision
                    : $idea->clusterDecision()->first();

                return [
                    'id'              => $idea->id,
                    'title'           => $idea->title,
                    'description'     => $idea->description,
                    'source'          => $idea->source?->value,
                    'author_email'    => $idea->author_email,
                    'created_at'      => $idea->created_at?->toISOString(),
                    'cluster_decision' => $decision === null ? null : [
                        'action'    => $decision->action?->value,
                        'rationale' => $decision->rationale,
                        'run_id'    => $decision->run_id,
                    ],
                ];
            })->all();
        }

        return $payload;
    }
}
