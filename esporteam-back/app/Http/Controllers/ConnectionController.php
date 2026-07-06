<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConnectionRequest;
use App\Http\Requests\UpdateConnectionRequest;
use App\Http\Resources\ConnectionResource;
use App\Models\Connection;
use App\Services\ConnectionService;
use Illuminate\Http\JsonResponse;

class ConnectionController extends Controller
{
    public function __construct(
        private readonly ConnectionService $connections,
    ) {}

    public function store(StoreConnectionRequest $request): JsonResponse
    {
        $connection = $this->connections->createForUser(
            (int) $request->user()->id,
            (int) $request->validated('target_profile_id'),
            $request->validated('type'),
        );

        return $this->createdResponse(new ConnectionResource($connection), 'Connection created.');
    }

    public function update(UpdateConnectionRequest $request, Connection $connection): JsonResponse
    {
        $connection = $this->connections->updateForUser(
            (int) $request->user()->id,
            $connection,
            $request->validated('status'),
        );

        return $this->successResponse(new ConnectionResource($connection), 'Connection updated.');
    }
}
