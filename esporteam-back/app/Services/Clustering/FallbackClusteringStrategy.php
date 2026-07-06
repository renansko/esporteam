<?php

namespace App\Services\Clustering;

use App\Enums\ClusteringDecisionAction;
use App\Enums\RoadmapItemOrigin;
use App\Models\ClusteringDecision;
use App\Models\ClusteringRun;
use App\Models\Idea;
use App\Models\RoadmapItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fallback determinístico: 1 Idea = 1 RoadmapItem isolado.
 *
 * Score breakdown padrão {3,3,3} → score 3.0. Origem 'fallback'.
 * Marca run.fallback_used = true.
 *
 * @wiki app/brain/services/FallbackClusteringStrategy.md
 */
class FallbackClusteringStrategy
{
    /**
     * @param  Collection<int,Idea>  $ideas
     * @return array{items_created:int,items_assigned:int}
     */
    public function execute(ClusteringRun $run, Collection $ideas, string $reason = ''): array
    {
        Log::channel('clustering')->warning('clustering.fallback.triggered', [
            'run_id'       => $run->id,
            'workspace_id' => $run->workspace_id,
            'reason'       => $reason,
            'ideas_count'  => $ideas->count(),
        ]);

        $created = 0;

        DB::transaction(function () use ($run, $ideas, $reason, &$created) {
            foreach ($ideas as $idea) {
                $item = new RoadmapItem([
                    'workspace_id'    => $run->workspace_id,
                    'title'           => Str::limit((string) ($idea->title ?? $idea->description), 60),
                    'description'     => (string) $idea->description,
                    'origin'          => RoadmapItemOrigin::Fallback->value,
                    'score_breakdown' => ['impact' => 3, 'reach' => 3, 'effort' => 3],
                    'votes_count'     => 1,
                ]);
                $item->recomputeScore();
                $item->save();

                $idea->roadmap_item_id = $item->id;
                $idea->save();

                ClusteringDecision::create([
                    'run_id'          => $run->id,
                    'idea_id'         => $idea->id,
                    'roadmap_item_id' => $item->id,
                    'action'          => ClusteringDecisionAction::Create->value,
                    'rationale'       => "Fallback determinístico: {$reason}. Item criado 1:1 a partir da Idea #{$idea->id}.",
                ]);

                $created++;
            }

            $run->fallback_used = true;
        });

        return ['items_created' => $created, 'items_assigned' => 0];
    }
}
