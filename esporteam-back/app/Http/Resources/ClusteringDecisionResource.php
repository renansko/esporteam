<?php

namespace App\Http\Resources;

use App\Models\ClusteringDecision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @wiki app/brain/resources/ClusteringDecisionResource.md
 *
 * @mixin ClusteringDecision
 */
class ClusteringDecisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $idea = $this->whenLoaded('idea');
        $item = $this->whenLoaded('roadmapItem');

        return [
            'id'         => $this->id,
            'run_id'     => $this->run_id,
            'action'     => $this->action?->value,
            'rationale'  => $this->rationale,
            'created_at' => $this->created_at?->toISOString(),
            'idea'       => $idea instanceof \App\Models\Idea ? [
                'id'          => $idea->id,
                'title'       => $idea->title,
                'description' => $idea->description,
            ] : null,
            'roadmap_item' => $item instanceof \App\Models\RoadmapItem ? [
                'id'    => $item->id,
                'title' => $item->title,
                'score' => (float) $item->score,
            ] : null,
        ];
    }
}
