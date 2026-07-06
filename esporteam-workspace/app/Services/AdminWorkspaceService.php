<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminWorkspaceService
{
    public function __construct(
        private AuditLogClient $auditLogClient,
    ) {}

    public function listAll(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Workspace::query()
            ->with(['members' => function ($q) {
                $q->where('role', 'owner');
            }])
            ->withCount('members');

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['slug'])) {
            $query->where('slug', 'like', '%' . $filters['slug'] . '%');
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function getWithStats(Workspace $workspace): array
    {
        $workspace->loadCount(['members', 'invites']);
        $owner = $workspace->members()->where('role', 'owner')->first();

        return array_merge($workspace->toArray(), [
            'members_count' => $workspace->members_count,
            'invites_count' => $workspace->invites_count,
            'owner'         => $owner,
            'is_active'     => $workspace->is_active,
            'created_at'    => $workspace->created_at,
        ]);
    }

    public function setStatus(int $adminId, string $adminEmail, Workspace $workspace, bool $active): Workspace
    {
        $workspace->update(['is_active' => $active]);

        $this->auditLogClient->log(
            $adminId,
            $adminEmail,
            $active ? 'activate_workspace' : 'deactivate_workspace',
            'workspace',
            $workspace->id,
            [
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
            []
        );

        return $workspace->fresh();
    }
}
