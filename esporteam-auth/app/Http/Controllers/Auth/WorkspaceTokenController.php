<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\JwtService;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceTokenController extends Controller
{
    public function __construct(
        private JwtService $jwt,
        private WorkspaceService $workspace,
    ) {}

    public function select(Request $request): JsonResponse
    {
        $request->validate([
            'workspace_id' => 'required|numeric',
        ]);

        $workspaceId = $request->input('workspace_id');
        $token = $request->bearerToken();
        $user = $request->user();

        $isEsporteamAdmin = (($user->permissions ?? 0) & 2) === 2;

        $membership = $this->workspace->validateMembership($workspaceId, $token, $user->id);

        if (!$membership['valid'] && !$isEsporteamAdmin) {
            return $this->errorResponse(__('messages.workspace.access_denied'), null, 403);
        }

        $isOwner = $membership['is_owner'];

        // school_permissions NÃO entram mais no JWT — passam a ser servidas
        // sob demanda pelo esporteam-school via GET /api/me/permissions, sempre
        // frescas da DB. Isto resolve o cenário em que mudar role/perm de um
        // staff exigia esperar o JWT (TTL longo) expirar pra refletir.
        $newToken = $this->jwt->encode($user->toArray(), $workspaceId, null, $isOwner);

        return $this->successResponse([
            'token' => $newToken,
        ], __('messages.workspace.selected'));
    }
}
