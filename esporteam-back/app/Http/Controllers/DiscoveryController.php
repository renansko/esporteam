<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexDiscoveryRequest;
use App\Http\Resources\DiscoveryCardResource;
use App\Services\DiscoveryService;
use Illuminate\Http\JsonResponse;

class DiscoveryController extends Controller
{
    public function __construct(
        private readonly DiscoveryService $discovery,
    ) {}

    public function index(IndexDiscoveryRequest $request): JsonResponse
    {
        $cards = $this->discovery->profilesForUser(
            (int) $request->user()->id,
            $request->validated(),
        );

        return $this->successResponse(DiscoveryCardResource::collection($cards), 'Discovery profiles listed.');
    }
}
