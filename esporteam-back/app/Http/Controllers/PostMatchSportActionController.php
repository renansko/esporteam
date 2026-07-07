<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexPostMatchSportActionRequest;
use App\Http\Requests\StorePostMatchSportActionSessionRequest;
use App\Http\Resources\PostMatchSportActionResource;
use App\Http\Resources\SportSessionResource;
use App\Services\PostMatchSportActionService;
use Illuminate\Http\JsonResponse;

class PostMatchSportActionController extends Controller
{
    public function __construct(
        private readonly PostMatchSportActionService $postMatchActions,
    ) {}

    public function index(IndexPostMatchSportActionRequest $request): JsonResponse
    {
        return $this->successResponse(
            new PostMatchSportActionResource($this->postMatchActions->actionsForUser(
                (int) $request->user()->id,
                $request->validated(),
            )),
            'Post-match actions listed.',
        );
    }

    public function saveSession(StorePostMatchSportActionSessionRequest $request): JsonResponse
    {
        $session = $this->postMatchActions->saveSessionForUser(
            (int) $request->user()->id,
            $request->validated(),
        );

        return $this->createdResponse(new SportSessionResource($session), 'Post-match session saved.');
    }
}
