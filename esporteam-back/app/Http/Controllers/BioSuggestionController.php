<?php

namespace App\Http\Controllers;

use App\Exceptions\BioSuggestionGenerationFailed;
use App\Exceptions\InsufficientBioContext;
use App\Exceptions\UnsafeBioSuggestion;
use App\Http\Requests\StoreBioSuggestionRequest;
use App\Http\Resources\BioSuggestionResource;
use App\Services\BioSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BioSuggestionController extends Controller
{
    public function __construct(private readonly BioSuggestionService $suggestions) {}

    public function index(Request $request): JsonResponse
    {
        $suggestions = $this->suggestions->listForUser((int) $request->user()->id);

        return $this->successResponse(BioSuggestionResource::collection($suggestions));
    }

    public function store(StoreBioSuggestionRequest $request): JsonResponse
    {
        try {
            $suggestion = $this->suggestions->createForUser(
                (int) $request->user()->id,
                $request->validated('instruction'),
            );
        } catch (InsufficientBioContext|UnsafeBioSuggestion $e) {
            return $this->errorResponse($e->getMessage(), ['context' => $e->getMessage()], 422);
        } catch (BioSuggestionGenerationFailed $e) {
            return $this->errorResponse($e->getMessage(), ['code' => 'provider_unavailable'], 503);
        }

        return $this->createdResponse(new BioSuggestionResource($suggestion), 'Bio suggestion created.');
    }
}
