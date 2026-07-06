<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexRoadmapRequest;
use App\Http\Resources\RoadmapItemResource;
use App\Models\RoadmapItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @wiki app/brain/services/RoadmapController.md
 */
class RoadmapController extends Controller
{
    public function index(IndexRoadmapRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $paginator = RoadmapItem::query()
            ->where('workspace_id', $request->workspace_id())
            ->withCount('ideas')
            ->when($validated['status']     ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($validated['origin']     ?? null, fn ($q, $v) => $q->where('origin', $v))
            ->when($validated['visibility'] ?? null, fn ($q, $v) => $q->where('visibility', $v))
            ->orderByDesc('score')
            ->orderByDesc('votes_count')
            ->orderByDesc('id')
            ->cursorPaginate(50);

        return $this->paginatedResponse(RoadmapItemResource::collection($paginator));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = RoadmapItem::query()
            ->withCount('ideas')
            ->with(['ideas.clusterDecision'])
            ->where('workspace_id', $request->workspace_id())
            ->where('id', $id)
            ->first();

        if (! $item) {
            throw new NotFoundHttpException();
        }

        return $this->successResponse(new RoadmapItemResource($item));
    }
}
