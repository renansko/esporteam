<?php

namespace App\Services\Clustering;

use App\Enums\ClusteringRunStatus;
use App\Models\ClusteringRun;
use App\Models\Idea;
use App\Models\RoadmapItem;
use App\Services\Llm\LlmException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orquestrador da run: carrega contexto, escolhe estratégia (LLM ou fallback
 * direto se circuit breaker aberto), persiste resultado e fecha a run.
 *
 * @wiki app/brain/services/ClusteringService.md
 */
class ClusteringService
{
    public function __construct(
        private readonly LlmClusteringStrategy $llmStrategy,
        private readonly FallbackClusteringStrategy $fallbackStrategy,
        private readonly CircuitBreaker $circuitBreaker,
    ) {}

    public function executeRun(ClusteringRun $run): void
    {
        $workspaceId = (int) $run->workspace_id;

        Log::channel('clustering')->info('clustering.run.started', [
            'run_id'       => $run->id,
            'workspace_id' => $workspaceId,
        ]);

        $startedMicro = microtime(true);

        try {
            $ideas = Idea::query()
                ->where('workspace_id', $workspaceId)
                ->whereNull('roadmap_item_id')
                ->orderBy('id')
                ->get();

            $existingItems = RoadmapItem::query()
                ->where('workspace_id', $workspaceId)
                ->orderByDesc('score')
                ->get();

            $run->ideas_processed = $ideas->count();

            if ($ideas->isEmpty()) {
                $this->complete($run, ['items_created' => 0, 'items_assigned' => 0]);
                return;
            }

            // Circuit breaker — pular LLM direto pro fallback.
            if ($this->circuitBreaker->isOpen($workspaceId)) {
                Log::channel('clustering')->warning('clustering.circuit_breaker.opened', [
                    'workspace_id' => $workspaceId,
                    'failures'     => $this->circuitBreaker->failureCount($workspaceId),
                ]);
                $result = $this->fallbackStrategy->execute($run, $ideas, 'circuit breaker open');
                $this->complete($run, $result);
                return;
            }

            try {
                $result = $this->llmStrategy->execute($run, $ideas, $existingItems);

                // Qualquer Idea que ficou órfã (validação rejeitou OU LLM esqueceu/alucinou)
                // cai no fallback dentro da MESMA run, garantindo 1 destino por Idea.
                $orphans = Idea::query()
                    ->where('workspace_id', $workspaceId)
                    ->whereIn('id', $ideas->pluck('id'))
                    ->whereNull('roadmap_item_id')
                    ->get();
                if ($orphans->isNotEmpty()) {
                    $fb = $this->fallbackStrategy->execute($run, $orphans, 'orphan ideas after LLM run');
                    $result['items_created'] = ($result['items_created'] ?? 0) + $fb['items_created'];
                }

                $run->token_usage_in            = $result['token_usage_in'];
                $run->token_usage_out           = $result['token_usage_out'];
                $run->cache_hit_rate            = $result['cache_hit_rate'];
                $run->llm_model                 = $result['llm_model'];
                $run->prompt_version            = $result['prompt_version'];
                $run->pre_cluster_bundles_count = $result['bundles'];
                $run->summary                   = $result['summary'] ?? null;

                $this->circuitBreaker->recordSuccess($workspaceId);
                $this->complete($run, $result);
            } catch (LlmException $e) {
                $this->circuitBreaker->recordFailure($workspaceId);
                Log::channel('clustering')->warning('clustering.fallback.triggered', [
                    'run_id'       => $run->id,
                    'reason'       => $e->getMessage(),
                ]);
                $result = $this->fallbackStrategy->execute($run, $ideas, $e->getMessage());
                $run->failure_reason = $e->getMessage();
                $this->complete($run, $result);
            }
        } catch (Throwable $e) {
            Log::channel('clustering')->error('clustering.run.failed', [
                'run_id' => $run->id,
                'reason' => $e->getMessage(),
            ]);
            $run->status         = ClusteringRunStatus::Failed->value;
            $run->failure_reason = $e->getMessage();
            $run->completed_at   = now();
            $run->save();
            throw $e;
        }

        $durationMs = (int) round((microtime(true) - $startedMicro) * 1000);
        Log::channel('clustering')->info('clustering.run.completed', [
            'run_id'         => $run->id,
            'duration_ms'    => $durationMs,
            'tokens_in'      => $run->token_usage_in,
            'tokens_out'     => $run->token_usage_out,
            'cache_hit_rate' => $run->cache_hit_rate,
            'items_created'  => $run->items_created,
            'items_assigned' => $run->items_assigned,
        ]);
    }

    /** @param  array<string,mixed>  $result */
    private function complete(ClusteringRun $run, array $result): void
    {
        $run->status         = ClusteringRunStatus::Done->value;
        $run->completed_at   = now();
        $run->items_created  = (int) ($result['items_created']  ?? 0);
        $run->items_assigned = (int) ($result['items_assigned'] ?? 0);
        $run->save();
    }
}
