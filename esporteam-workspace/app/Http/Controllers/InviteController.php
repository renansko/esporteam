<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use App\Models\WorkspaceInvite;
use App\Services\InviteService;
use App\Services\MemberService;
use App\Traits\AuthorizesWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InviteController extends Controller
{
    use AuthorizesWorkspace;

    public function __construct(
        private InviteService $inviteService,
        private MemberService $memberService,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeRole($request, $workspace, [WorkspaceRole::Owner->value, WorkspaceRole::Admin->value]);

        $invites = $this->inviteService->listPending($workspace);

        return $this->successResponse($invites, __('messages.invite.retrieved'));
    }

    public function store(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorizeRole($request, $workspace, [WorkspaceRole::Owner->value, WorkspaceRole::Admin->value]);

        $request->validate([
            'email' => 'required|email',
            'role' => ['sometimes', 'string', WorkspaceRole::assignableValidationRule()],
        ]);

        if ($this->inviteService->hasPendingInvite($workspace, $request->email)) {
            return $this->errorResponse(__('messages.invite.already_sent'), null, 409);
        }

        $invite = $this->inviteService->create(
            $workspace,
            $request->email,
            $request->input('role', WorkspaceRole::Member->value)
        );

        return $this->createdResponse($invite, __('messages.invite.created'));
    }

    public function destroy(Request $request, Workspace $workspace, WorkspaceInvite $invite): JsonResponse
    {
        $this->authorizeRole($request, $workspace, [WorkspaceRole::Owner->value, WorkspaceRole::Admin->value]);

        if ($invite->workspace_id !== $workspace->id) {
            abort(404, __('messages.invite.not_found'));
        }

        $this->inviteService->revoke($invite);
        return $this->deletedResponse();
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invite = $this->inviteService->findByToken($token);

        if ($invite->isExpired()) {
            return $this->errorResponse(__('messages.invite.expired'), null, 410);
        }

        $userId = $request->user()->id;

        if ($this->memberService->isMember($invite->workspace, $userId)) {
            $this->inviteService->revoke($invite);
            return $this->errorResponse(__('messages.invite.already_member'), null, 409);
        }

        $this->inviteService->accept($invite, $userId);

        return $this->successResponse([
            'workspace_id' => $invite->workspace_id,
        ], __('messages.invite.accepted'));
    }

    public function storeService(Request $request, Workspace $workspace): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'role'  => ['sometimes', 'string', WorkspaceRole::assignableValidationRule()],
        ]);

        $this->inviteService->revokeByEmail($workspace, $request->email);

        $invite = $this->inviteService->create(
            $workspace,
            $request->email,
            $request->input('role', WorkspaceRole::Member->value)
        );

        return $this->createdResponse($invite, __('messages.invite.created'));
    }

    public function acceptService(Request $request, string $token): JsonResponse
    {
        $request->validate(['user_id' => 'required|integer']);

        $invite = $this->inviteService->findByToken($token);

        if ($invite->isExpired()) {
            return $this->errorResponse(__('messages.invite.expired'), null, 410);
        }

        $userId = $request->integer('user_id');

        if ($this->memberService->isMember($invite->workspace, $userId)) {
            $this->inviteService->revoke($invite);
            return $this->errorResponse(__('messages.invite.already_member'), null, 409);
        }

        $member = $this->inviteService->accept($invite, $userId);

        return $this->successResponse([
            'workspace_id' => $member->workspace_id,
            'user_id'      => $member->user_id,
            'role'         => $member->role,
        ], __('messages.invite.accepted'));
    }
}
