<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexDiscoveryRequest;
use App\Http\Resources\DiscoveryCardResource;
use App\Services\DiscoveryCache;
use App\Services\DiscoveryService;
use Illuminate\Http\JsonResponse;

class DiscoveryController extends Controller
{
    public function __construct(
        private readonly DiscoveryCache $cache,
        private readonly DiscoveryService $discovery,
    ) {}

    public function index(IndexDiscoveryRequest $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $filters = $request->validated();
        $payload = $this->cache->remember(
            'feed',
            $userId,
            $filters,
            function () use ($userId, $filters): array {
                $result = $this->discovery->discoverForUser($userId, $filters);

                return [
                    'mode' => $result['mode'],
                    'data' => DiscoveryCardResource::collection($result['cards'])->resolve(),
                    'empty_state' => $result['empty_state'],
                ];
            },
        );

        return response()->json([
            'success' => true,
            'message' => 'Discovery listed.',
            ...$payload,
        ]);
    }
}
