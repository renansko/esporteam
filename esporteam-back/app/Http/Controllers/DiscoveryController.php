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
        $result = $this->discovery->discoverForUser(
            (int) $request->user()->id,
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Discovery listed.',
            'mode' => $result['mode'],
            'data' => DiscoveryCardResource::collection($result['cards']),
            'empty_state' => $result['empty_state'],
        ]);
    }
}
