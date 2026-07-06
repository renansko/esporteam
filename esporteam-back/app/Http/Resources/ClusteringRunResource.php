<?php

namespace App\Http\Resources;

use App\Models\ClusteringRun;
use App\Services\Llm\CostCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @wiki app/brain/resources/ClusteringRunResource.md
 *
 * @mixin ClusteringRun
 */
class ClusteringRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cost = app(CostCalculator::class)->usd(
            (int) $this->token_usage_in,
            (int) $this->token_usage_out,
            (string) ($this->llm_model ?? '')
        );

        return [
            'id'                        => $this->id,
            'status'                    => $this->status?->value,
            'started_at'                => $this->started_at?->toISOString(),
            'completed_at'              => $this->completed_at?->toISOString(),
            'ideas_processed'           => $this->ideas_processed,
            'items_created'             => $this->items_created,
            'items_assigned'            => $this->items_assigned,
            'llm_model'                 => $this->llm_model,
            'prompt_version'            => $this->prompt_version,
            'token_usage_in'            => $this->token_usage_in,
            'token_usage_out'           => $this->token_usage_out,
            'cost_usd'                  => $cost,
            'cache_hit_rate'            => $this->cache_hit_rate === null ? null : (float) $this->cache_hit_rate,
            'pre_cluster_bundles_count' => $this->pre_cluster_bundles_count,
            'fallback_used'             => (bool) $this->fallback_used,
            'failure_reason'            => $this->failure_reason,
            'summary'                   => $this->summary,
            'created_at'                => $this->created_at?->toISOString(),
        ];
    }
}
