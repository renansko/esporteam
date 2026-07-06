<?php

namespace App\Services;

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Database\Eloquent\Collection;

class MemberService
{
    public function listByWorkspace(Workspace $workspace): Collection
    {
        return $workspace->members;
    }

    public function add(Workspace $workspace, int $userId, string $role): WorkspaceMember
    {
        return WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $userId,
            'role' => $role,
        ]);
    }

    public function findByWorkspaceAndUser(Workspace $workspace, int $userId): WorkspaceMember
    {
        return $workspace->members()
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    public function isMember(Workspace $workspace, int $userId): bool
    {
        return $workspace->members()
            ->where('user_id', $userId)
            ->exists();
    }

    public function updateRole(WorkspaceMember $member, string $role): WorkspaceMember
    {
        $member->update(['role' => $role]);
        return $member;
    }

    public function remove(WorkspaceMember $member): void
    {
        $member->delete();
    }
}
