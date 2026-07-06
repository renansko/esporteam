<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImpersonateRequest;
use App\Http\Resources\UserResource;
use App\Services\ImpersonationService;
use Illuminate\Http\JsonResponse;

class ImpersonationController extends Controller
{
    public function __construct(private ImpersonationService $impersonation) {}

    public function impersonate(ImpersonateRequest $request): JsonResponse
    {
        $data = $request->validated();

        $result = $this->impersonation->impersonate(
            $request->user(),
            (int) $data['user_id'],
            isset($data['workspace_id']) ? (int) $data['workspace_id'] : null,
        );

        return $this->successResponse([
            'token'      => $result['token'],
            'expires_at' => $result['expires_at'],
            'user'       => new UserResource($result['user']),
        ], __('messages.admin.impersonation.created'));
    }
}
