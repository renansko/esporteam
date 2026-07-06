<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WorkspaceRole;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Services\AdminWorkspaceMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminWorkspaceMemberController extends Controller
{
    public function __construct(
        private AdminWorkspaceMemberService $adminWorkspaceMemberService,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $validated = $request->validate([
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'role'     => ['nullable', 'string', WorkspaceRole::validationRule()],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 25);
        $filters = [
            'role' => $validated['role'] ?? null,
        ];

        $result = $this->adminWorkspaceMemberService->listByWorkspace($workspace, $filters, $perPage);

        return $this->successResponse($result, 'Workspace members retrieved successfully');
    }
}
