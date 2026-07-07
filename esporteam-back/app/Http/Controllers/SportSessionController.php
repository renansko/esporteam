<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexSportSessionRequest;
use App\Http\Requests\StoreSportSessionRequest;
use App\Http\Resources\SportSessionResource;
use App\Models\SportSession;
use App\Services\SportSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SportSessionController extends Controller
{
    public function __construct(
        private readonly SportSessionService $sessions,
    ) {}

    public function index(IndexSportSessionRequest $request): JsonResponse
    {
        return $this->successResponse(
            SportSessionResource::collection($this->sessions->openSessions($request->validated())),
            'Sessions listed.',
        );
    }

    public function store(StoreSportSessionRequest $request): JsonResponse
    {
        $session = $this->sessions->createForUser(
            (int) $request->user()->id,
            $request->validated(),
        );

        return $this->createdResponse(new SportSessionResource($session), 'Session created.');
    }

    public function join(Request $request, SportSession $session): JsonResponse
    {
        $session = $this->sessions->join((int) $request->user()->id, $session);

        return $this->createdResponse(new SportSessionResource($session), 'Session joined.');
    }
}
