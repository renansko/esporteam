<?php

namespace App\Http\Controllers;

use App\Enums\IdeaSource;
use App\Http\Requests\StoreIdeaRequest;
use App\Http\Resources\IdeaResource;
use App\Models\Idea;
use App\Services\IdeaIngestionService;
use App\Services\IngestIdeaInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IdeaController extends Controller
{
    public function __construct(
        private readonly IdeaIngestionService $ingestionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:200'],
            'page'        => ['nullable', 'integer', 'min:1'],
            'source'      => ['nullable', Rule::enum(IdeaSource::class)],
            'unclustered' => ['nullable'], // $request->boolean() faz a coerção
        ]);

        $query = Idea::query()
            ->where('workspace_id', $request->workspace_id())
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if (!empty($validated['source'])) {
            $query->where('source', $validated['source']);
        }
        if ($request->boolean('unclustered')) {
            $query->whereNull('roadmap_item_id');
        }

        $paginator = $query->paginate(perPage: (int) ($validated['per_page'] ?? 50));

        return $this->paginatedResponse(IdeaResource::collection($paginator));
    }

    public function store(StoreIdeaRequest $request): JsonResponse
    {
        $idea = $this->ingestionService->ingest(new IngestIdeaInput(
            workspaceId: (int) $request->workspace_id(),
            source: IdeaSource::Manual,
            description: $request->string('description')->toString(),
            title: $request->input('title'),
            authorEmail: $request->input('author_email'),
        ));

        return $this->createdResponse(new IdeaResource($idea), 'Idea created.');
    }
}
