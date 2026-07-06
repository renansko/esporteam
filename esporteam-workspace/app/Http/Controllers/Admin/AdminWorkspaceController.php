<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SetWorkspaceStatusRequest;
use App\Models\Workspace;
use App\Services\AdminWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminWorkspaceController extends Controller
{
    public function __construct(
        private AdminWorkspaceService $adminWorkspaceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['name', 'slug', 'is_active']);
        $perPage = (int) $request->input('per_page', 25);

        $paginator = $this->adminWorkspaceService->listAll($filters, $perPage);

        return $this->successResponse([
            'items' => $paginator->items(),
            'meta'  => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ], __('messages.workspace.retrieved'));
    }

    public function show(Workspace $workspace): JsonResponse
    {
        $data = $this->adminWorkspaceService->getWithStats($workspace);

        return $this->successResponse($data, __('messages.workspace.retrieved_detail'));
    }

    public function setStatus(SetWorkspaceStatusRequest $request, Workspace $workspace): JsonResponse
    {
        $user = $request->user();
        $adminId = (int) ($user->id ?? 0);
        $adminEmail = (string) ($user->email ?? '');

        $workspace = $this->adminWorkspaceService->setStatus(
            $adminId,
            $adminEmail,
            $workspace,
            (bool) $request->validated()['active']
        );

        return $this->successResponse(
            $workspace,
            $workspace->is_active
                ? __('messages.workspace.activated')
                : __('messages.workspace.deactivated_success')
        );
    }
}
