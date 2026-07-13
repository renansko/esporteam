<?php

namespace App\Http\Controllers;

use App\Exceptions\BioSuggestionGenerationFailed;
use App\Exceptions\InsufficientBioContext;
use App\Exceptions\UnsafeBioSuggestion;
use App\Http\Requests\IndexBioSuggestionRequest;
use App\Http\Requests\StoreBioSuggestionRequest;
use App\Http\Resources\BioSuggestionResource;
use App\Models\BioSuggestion;
use App\Services\BioSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BioSuggestionController extends Controller
{
    public function __construct(private readonly BioSuggestionService $suggestions) {}

    public function index(IndexBioSuggestionRequest $request): JsonResponse
    {
        $suggestions = $this->suggestions->listForUser(
            (int) $request->user()->id,
            (int) ($request->validated('per_page') ?? 10),
        );

        return $this->paginatedResponse(BioSuggestionResource::collection($suggestions), 'Bio suggestions listed.');
    }

    public function store(StoreBioSuggestionRequest $request): JsonResponse
    {
        try {
            $suggestion = $this->suggestions->createForUser(
                (int) $request->user()->id,
                $request->validated('instruction'),
                $request->validated('idempotency_key'),
            );
        } catch (InsufficientBioContext|UnsafeBioSuggestion $e) {
            return $this->codedErrorResponse(
                $e->getMessage(),
                $e instanceof UnsafeBioSuggestion ? $e->reason : 'insufficient_context',
                ['context' => [$e->getMessage()]],
                422,
            );
        } catch (BioSuggestionGenerationFailed $e) {
            return $this->codedErrorResponse($e->getMessage(), 'provider_unavailable', [], 503);
        }

        if ($suggestion->wasReplayed) {
            if ($suggestion->failure_code !== null) {
                return $this->storedFailureResponse($suggestion);
            }

            $response = $this->successResponse(new BioSuggestionResource($suggestion), 'Bio suggestion replayed.');
            $response->headers->set('Idempotent-Replayed', 'true');

            return $response;
        }

        return $this->createdResponse(new BioSuggestionResource($suggestion), 'Bio suggestion created.');
    }

    public function accept(Request $request, int $suggestion): JsonResponse
    {
        $accepted = $this->suggestions->acceptForUser((int) $request->user()->id, $suggestion);

        return $this->successResponse(new BioSuggestionResource($accepted), 'Bio suggestion accepted.');
    }

    private function storedFailureResponse(BioSuggestion $suggestion): JsonResponse
    {
        $status = $suggestion->failure_code === 'provider_unavailable' ? 503 : 422;
        $response = $this->codedErrorResponse(
            $status === 503 ? 'Não foi possível gerar uma sugestão de bio agora.' : 'A sugestão recebida não passou pelas validações de segurança.',
            (string) $suggestion->failure_code,
            [],
            $status,
        );
        $response->headers->set('Idempotent-Replayed', 'true');

        return $response;
    }

    private function codedErrorResponse(string $message, string $code, array $errors, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => $code,
            'errors' => (object) $errors,
        ], $status);
    }
}
