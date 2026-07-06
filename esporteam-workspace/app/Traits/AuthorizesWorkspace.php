<?php

namespace App\Traits;

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Request;

trait AuthorizesWorkspace
{
    private function authorizeAccess(Request $request, Workspace $workspace): void
    {
        if ($this->isEsporteamAdmin($request)) {
            return;
        }

        if (!$this->workspaceMember($request, $workspace)) {
            abort(403, __('messages.auth.not_member'));
        }
    }

    private function authorizeRole(Request $request, Workspace $workspace, array $roles): void
    {
        if ($this->isEsporteamAdmin($request)) {
            return;
        }

        $member = $this->workspaceMember($request, $workspace);

        if (!$member || !in_array($member->role, $roles)) {
            abort(403, __('messages.auth.no_permission'));
        }
    }

    private function isEsporteamAdmin(Request $request): bool
    {
        return (bool) ($request->user()->is_esporteam_admin ?? false);
    }

    private function workspaceMember(Request $request, Workspace $workspace): ?WorkspaceMember
    {
        return $workspace->members()
            ->where('user_id', $request->user()->id)
            ->first();
    }

    private function hasWorkspaceRole(Request $request, Workspace $workspace, array $roles): bool
    {
        if ($this->isEsporteamAdmin($request)) {
            return true;
        }

        $member = $this->workspaceMember($request, $workspace);

        return $member && in_array($member->role, $roles, true);
    }
}
