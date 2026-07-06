<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use App\Traits\AuthorizesWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    use AuthorizesWorkspace;

    public function __construct(
        private WorkspaceService $workspaceService,
    ) {}

    public function publicInfo(Workspace $workspace): JsonResponse
    {
        return $this->successResponse([
            'id'   => $workspace->id,
            'name' => $workspace->name,
            'slug' => $workspace->slug,
        ], __('messages.workspace.public_retrieved'));
    }

    public function index(Request $request): JsonResponse
    {
        $workspaces = $this->workspaceService->listByUser($request->user()->id);
        return $this->successResponse($workspaces, __('messages.workspace.retrieved'));
    }

    public function store(Request $request): JsonResponse
    {
        $permissions = $request->attributes->get('permissions', 1);
        if (!($permissions & 1)) {
            return $this->errorResponse(__('messages.workspace.cannot_create'), null, 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace = $this->workspaceService->create(
            $request->only('name'),
            $request->user()->id
        );

        return $this->createdResponse($workspace, __('messages.workspace.created'));
    }

    public function show(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeAccess($request, $workspace);

        $workspace->load('members');

        return $this->successResponse($workspace, __('messages.workspace.retrieved_detail'));
    }

    public function update(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeRole($request, $workspace, [WorkspaceRole::Owner->value]);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspace = $this->workspaceService->update($workspace, $request->only('name'));

        return $this->successResponse($workspace, __('messages.workspace.updated'));
    }

    public function destroy(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeRole($request, $workspace, [WorkspaceRole::Owner->value]);

        $this->workspaceService->delete($workspace);

        return $this->deletedResponse();
    }
}
