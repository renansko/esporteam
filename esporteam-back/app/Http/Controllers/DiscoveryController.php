<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexDiscoveryRequest;
use App\Http\Resources\SportProfileResource;
use App\Services\DiscoveryService;
use Illuminate\Http\JsonResponse;

class DiscoveryController extends Controller
{
    public function __construct(
        private readonly DiscoveryService $discovery,
    ) {}

    public function index(IndexDiscoveryRequest $request): JsonResponse
    {
        $profiles = $this->discovery->profilesForUser(
            (int) $request->user()->id,
            $request->validated(),
        );

        return $this->successResponse(SportProfileResource::collection($profiles), 'Discovery profiles listed.');
    }
}
