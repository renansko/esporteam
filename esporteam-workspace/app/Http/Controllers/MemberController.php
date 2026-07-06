<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use App\Services\MemberService;
use App\Traits\AuthorizesWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    use AuthorizesWorkspace;

    public function __construct(
        private MemberService $memberService,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeAccess($request, $workspace);

        $members = $this->memberService->listByWorkspace($workspace);
        return $this->successResponse($members, __('messages.member.retrieved'));
    }

    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeRole($request, $workspace, [WorkspaceRole::Owner->value, WorkspaceRole::Admin->value]);

        $request->validate([
            'user_id' => 'required|integer',
            'role' => ['required', 'string', WorkspaceRole::assignableValidationRule()],
        ]);

        if ($this->memberService->isMember($workspace, $request->user_id)) {
            return $this->errorResponse(__('messages.member.already_member'), null, 409);
        }

        $member = $this->memberService->add($workspace, $request->user_id, $request->role);
        return $this->createdResponse($member, __('messages.member.created'));
    }

    public function update(Request $request, Workspace $workspace, int $userId): JsonResponse
    {
        $this->authorizeRole($request, $workspace, [WorkspaceRole::Owner->value]);

        $request->validate([
            'role' => ['required', 'string', WorkspaceRole::assignableValidationRule()],
        ]);

        $member = $this->memberService->findByWorkspaceAndUser($workspace, $userId);

        if ($member->role === WorkspaceRole::Owner->value) {
            return $this->errorResponse(__('messages.member.cannot_change_owner'), null, 403);
        }

        $member = $this->memberService->updateRole($member, $request->role);
        return $this->successResponse($member, __('messages.member.updated'));
    }

    public function destroy(Request $request, Workspace $workspace, int $userId): JsonResponse
    {
        $this->authorizeRole($request, $workspace, [WorkspaceRole::Owner->value, WorkspaceRole::Admin->value]);

        $member = $this->memberService->findByWorkspaceAndUser($workspace, $userId);

        if ($member->role === WorkspaceRole::Owner->value) {
            return $this->errorResponse(__('messages.member.cannot_remove_owner'), null, 403);
        }

        $this->memberService->remove($member);
        return $this->deletedResponse();
    }
}
