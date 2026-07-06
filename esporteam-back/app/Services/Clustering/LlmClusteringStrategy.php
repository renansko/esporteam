<?php

namespace App\Services\Clustering;

use App\Enums\ClusteringDecisionAction;
use App\Enums\RoadmapItemOrigin;
use App\Models\ClusteringDecision;
use App\Models\ClusteringRun;
use App\Models\Idea;
use App\Models\RoadmapItem;
use App\Services\Llm\Contracts\LlmChatRequest;
use App\Services\Llm\Contracts\LlmClient;
use App\Services\Llm\LlmException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Caminho feliz: monta prompt, chama LLM, parseia JSON, valida e persiste.
 *
 * Retry 1x com backoff 1s/2s antes de propagar a exceção.
 *
 * @wiki app/brain/services/LlmClusteringStrategy.md
 */
class LlmClusteringStrategy
{
    public function __construct(
        private readonly LlmClient $llm,
        private readonly ClusteringPromptLoader $promptLoader,
        private readonly ClusteringPreClusterStrategy $preCluster,
        private readonly ClusteringDecisionValidator $validator,
    ) {}

    /**
     * @param  Collection<int,Idea>          $ideas
     * @param  Collection<int,RoadmapItem>   $existingItems
     * @return array{items_created:int,items_assigned:int,token_usage_in:int,token_usage_out:int,cache_hit_rate:float,llm_model:string,prompt_version:string,bundles:int,summary:?string,raw:array<string,mixed>}
     *
     * @throws LlmException quando todas as tentativas de chamada falham, ou quando
     *         o JSON retornado é inválido após parse + tentativa de salvamento.
     */
    public function execute(ClusteringRun $run, Collection $ideas, Collection $existingItems): array
    {
        $promptVersion = (string) config('llm.clustering_prompt');
        $model = (string) config('llm.clustering_model');

        $bundles = $this->preCluster->bundle($ideas);
        // Para envio ao LLM usamos só o representante de cada bundle.
        $representativeIdeas = collect($bundles)->map(fn (IdeaBundle $b) => $b->representative);
        $rendered = $this->promptLoader->render($promptVersion, $existingItems, $representativeIdeas);

        $request = new LlmChatRequest(
            system: $rendered,
            messages: [['role' => 'user', 'content' => 'Cluster as Ideas above. Respond with JSON only.']],
            model: $model,
            responseFormat: 'json',
            cacheSegments: [
                ['name' => 'workspace_items', 'content' => 'Existing items context'],
            ],
        );

        $response = $this->callWithRetry($request, $run);

        $payload = json_decode($response->content, true);
        if (! is_array($payload) || ! isset($payload['decisions']) || ! is_array($payload['decisions'])) {
            throw LlmException::parse('missing decisions[] in response');
        }

        $allowedIdeaIds = $ideas->pluck('id')->map(fn ($v) => (int) $v)->all();
        $allowedItemIds = $existingItems->pluck('id')->map(fn ($v) => (int) $v)->all();

        $created  = 0;
        $assigned = 0;
        $rejectedByIdeaId = [];

        DB::transaction(function () use (
            $payload, $allowedIdeaIds, $allowedItemIds, $bundles, $run,
            &$created, &$assigned, &$rejectedByIdeaId
        ) {
            foreach ($payload['decisions'] as $decisionRaw) {
                $cleaned = $this->validator->validate($decisionRaw, $allowedIdeaIds, $allowedItemIds);
                if ($cleaned === null) {
                    $ideaIdRaw = (int) ($decisionRaw['idea_id'] ?? 0);
                    if ($ideaIdRaw > 0) {
                        $rejectedByIdeaId[$ideaIdRaw] = true;
                    }
                    continue;
                }

                if ($cleaned['action'] === 'assign') {
                    $itemId = (int) $cleaned['roadmap_item_id'];
                    $this->applyDecision($run, $cleaned, $bundles, $itemId);
                    $assigned += $this->bundleSize($bundles, (int) $cleaned['idea_id']);
                    continue;
                }

                // create
                $item = new RoadmapItem([
                    'workspace_id'    => $run->workspace_id,
                    'title'           => $cleaned['new_item']['title'],
                    'description'     => $cleaned['new_item']['description'],
                    'origin'          => RoadmapItemOrigin::Clustered->value,
                    'score_breakdown' => $cleaned['new_item']['score_breakdown'],
                    'votes_count'     => 0,
                ]);
                $item->recomputeScore();
                $item->save();

                $this->applyDecision($run, $cleaned, $bundles, $item->id);
                $created++;
            }

            // Recomputa votes_count + score em todos os itens tocados (assign incrementa votes).
            $touched = RoadmapItem::query()
                ->where('workspace_id', $run->workspace_id)
                ->whereIn('id', RoadmapItem::query()
                    ->where('workspace_id', $run->workspace_id)
                    ->pluck('id'))
                ->get();

            foreach ($touched as $it) {
                $count = $it->ideas()->count();
                if ($count !== (int) $it->votes_count) {
                    $it->votes_count = $count;
                    $it->save();
                }
            }
        });

        $totalIn = max(1, (int) $response->tokensIn);
        $cacheHitRate = round(($response->tokensCached / $totalIn) * 100, 2);

        return [
            'items_created'   => $created,
            'items_assigned'  => $assigned,
            'token_usage_in'  => $response->tokensIn,
            'token_usage_out' => $response->tokensOut,
            'cache_hit_rate'  => $cacheHitRate,
            'llm_model'       => $response->modelUsed ?: $model,
            'prompt_version'  => $promptVersion,
            'bundles'         => $bundles->count(),
            'summary'         => $payload['summary'] ?? null,
            'rejected_idea_ids' => array_keys($rejectedByIdeaId),
            'raw'             => ['payload_decisions_count' => count($payload['decisions'])],
        ];
    }

    /**
     * Cria decisões no banco para o representante + siblings.
     *
     * @param  array<string,mixed>           $cleaned
     * @param  Collection<int,IdeaBundle>    $bundles
     */
    private function applyDecision(ClusteringRun $run, array $cleaned, Collection $bundles, int $itemId): void
    {
        $bundle = $this->bundleOf($bundles, (int) $cleaned['idea_id']);
        $ideaIds = $bundle?->ideaIds() ?? [(int) $cleaned['idea_id']];

        $rationale = (string) ($cleaned['rationale'] ?? '');
        if ($bundle && $bundle->size() > 1) {
            $rationale .= ' [pre-cluster bundle: '.implode(',', $ideaIds).']';
        }

        Idea::query()->whereIn('id', $ideaIds)->update(['roadmap_item_id' => $itemId]);

        foreach ($ideaIds as $ideaId) {
            ClusteringDecision::create([
                'run_id'          => $run->id,
                'idea_id'         => $ideaId,
                'roadmap_item_id' => $itemId,
                'action'          => $cleaned['action'] === 'assign'
                    ? ClusteringDecisionAction::Assign->value
                    : ClusteringDecisionAction::Create->value,
                'rationale'       => $rationale,
            ]);
        }
    }

    /** @param  Collection<int,IdeaBundle>  $bundles */
    private function bundleOf(Collection $bundles, int $ideaId): ?IdeaBundle
    {
        foreach ($bundles as $b) {
            if (in_array($ideaId, $b->ideaIds(), true)) {
                return $b;
            }
        }
        return null;
    }

    /** @param  Collection<int,IdeaBundle>  $bundles */
    private function bundleSize(Collection $bundles, int $ideaId): int
    {
        return $this->bundleOf($bundles, $ideaId)?->size() ?? 1;
    }

    private function callWithRetry(LlmChatRequest $request, ClusteringRun $run): \App\Services\Llm\Contracts\LlmChatResponse
    {
        $attempts = 0;
        $delays = [1, 2];
        $last = null;

        do {
            try {
                return $this->llm->chat($request);
            } catch (LlmException $e) {
                $last = $e;
                $attempts++;
                Log::channel('clustering')->warning('clustering.llm.retry', [
                    'run_id'   => $run->id,
                    'attempt'  => $attempts,
                    'error'    => $e->getMessage(),
                ]);
                if ($attempts <= count($delays) && ! defined('APP_RUNNING_TESTS')) {
                    sleep($delays[$attempts - 1]);
                }
            }
        } while ($attempts <= count($delays));

        throw $last;
    }
}
