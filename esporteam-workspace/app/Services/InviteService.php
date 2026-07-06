<?php

namespace App\Services;

use App\Models\Workspace;
use App\Models\WorkspaceInvite;
use App\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class InviteService
{
    public function listPending(Workspace $workspace): Collection
    {
        return $workspace->invites()
            ->where('expires_at', '>', now())
            ->get();
    }

    public function create(Workspace $workspace, string $email, string $role): WorkspaceInvite
    {
        return WorkspaceInvite::create([
            'workspace_id' => $workspace->id,
            'email' => $email,
            'role' => $role,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function findByToken(string $token): WorkspaceInvite
    {
        return WorkspaceInvite::where('token', $token)->firstOrFail();
    }

    public function hasPendingInvite(Workspace $workspace, string $email): bool
    {
        return $workspace->invites()
            ->where('email', $email)
            ->where('expires_at', '>', now())
            ->exists();
    }

    public function accept(WorkspaceInvite $invite, int $userId): WorkspaceMember
    {
        $member = WorkspaceMember::create([
            'workspace_id' => $invite->workspace_id,
            'user_id' => $userId,
            'role' => $invite->role,
        ]);

        $invite->delete();

        return $member;
    }

    public function revoke(WorkspaceInvite $invite): void
    {
        $invite->delete();
    }

    public function revokeByEmail(Workspace $workspace, string $email): void
    {
        $workspace->invites()->where('email', $email)->delete();
    }
}
