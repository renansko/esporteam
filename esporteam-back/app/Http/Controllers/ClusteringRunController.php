<?php

namespace App\Http\Controllers;

use App\Enums\ClusteringRunStatus;
use App\Http\Requests\IndexClusteringRunsRequest;
use App\Http\Resources\ClusteringDecisionResource;
use App\Http\Resources\ClusteringRunResource;
use App\Jobs\ClusterIdeasJob;
use App\Models\ClusteringDecision;
use App\Models\ClusteringRun;
use App\Models\Idea;
use App\Services\Llm\CostCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @wiki app/brain/services/ClusteringRunController.md
 */
class ClusteringRunController extends Controller
{
    public function __construct(private readonly CostCalculator $costCalculator) {}

    public function store(Request $request): JsonResponse
    {
        $workspaceId = (int) $request->workspace_id();

        // Cost guard — bloqueia se já estourou o budget mensal.
        if ($this->costCalculator->workspaceOverBudget($workspaceId)) {
            return $this->errorResponse('Monthly token budget exceeded for this workspace.', null, 402);
        }

        // Lock — só 1 run ativo por workspace.
        $existing = ClusteringRun::query()
            ->where('workspace_id', $workspaceId)
            ->where('status', ClusteringRunStatus::Running->value)
            ->orderByDesc('id')
            ->first();
        if ($existing) {
            return $this->errorResponse('A clustering run is already in progress for this workspace.', [
                'existing_run_id' => $existing->id,
                'started_at'      => $existing->started_at?->toISOString(),
            ], 409);
        }

        $hasUnclustered = Idea::query()
            ->where('workspace_id', $workspaceId)
            ->whereNull('roadmap_item_id')
            ->exists();
        if (! $hasUnclustered) {
            return $this->errorResponse('No unclustered ideas to analyze.', null, 422);
        }

        $run = ClusteringRun::create([
            'workspace_id'   => $workspaceId,
            'status'         => ClusteringRunStatus::Running->value,
            'started_at'     => now(),
            'llm_model'      => config('llm.clustering_model'),
            'prompt_version' => config('llm.clustering_prompt'),
            'fallback_used'  => false,
        ]);

        ClusterIdeasJob::dispatch($run->id);

        return $this->successResponse([
            'run_id' => $run->id,
            'status' => $run->status?->value,
        ], 'Clustering dispatched.', 202);
    }

    public function index(IndexClusteringRunsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $paginator = ClusteringRun::query()
            ->where('workspace_id', $request->workspace_id())
            ->when($validated['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($request->filled('fallback_used'), fn ($q) => $q->where('fallback_used', $request->boolean('fallback_used')))
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->cursorPaginate(50);

        return $this->paginatedResponse(ClusteringRunResource::collection($paginator));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $run = ClusteringRun::query()
            ->where('workspace_id', $request->workspace_id())
            ->where('id', $id)
            ->first();
        if (! $run) {
            throw new NotFoundHttpException();
        }

        return $this->successResponse(new ClusteringRunResource($run));
    }

    public function decisions(Request $request, int $id): JsonResponse
    {
        $run = ClusteringRun::query()
            ->where('workspace_id', $request->workspace_id())
            ->where('id', $id)
            ->first();
        if (! $run) {
            throw new NotFoundHttpException();
        }

        $paginator = ClusteringDecision::query()
            ->where('run_id', $run->id)
            ->with(['idea', 'roadmapItem'])
            ->orderBy('id')
            ->cursorPaginate(50);

        return $this->paginatedResponse(ClusteringDecisionResource::collection($paginator));
    }
}
