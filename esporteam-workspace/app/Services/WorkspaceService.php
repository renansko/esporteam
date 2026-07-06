<?php

namespace App\Services;

use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Enums\WorkspaceRole;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class WorkspaceService
{
    public function listAll(): Collection
    {
        return Workspace::with(['members' => function ($query) {
            $query->where('role', WorkspaceRole::Owner->value);
        }])->get();
    }

    public function listByUser(int $userId): Collection
    {
        return Workspace::whereHas('members', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->get();
    }

    public function create(array $data, int $ownerId): Workspace
    {
        $workspace = Workspace::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']) . '-' . Str::random(6),
            'owner_id' => $ownerId,
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $ownerId,
            'role' => WorkspaceRole::Owner->value,
        ]);

        return $workspace;
    }

    public function update(Workspace $workspace, array $data): Workspace
    {
        $workspace->update(['name' => $data['name']]);
        return $workspace;
    }

    public function delete(Workspace $workspace): void
    {
        $workspace->delete();
    }
}
