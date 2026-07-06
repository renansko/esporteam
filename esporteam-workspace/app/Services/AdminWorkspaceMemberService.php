<?php

namespace App\Services;

use App\Models\Workspace;

class AdminWorkspaceMemberService
{
    public function __construct(
        private AuthUserClient $authUserClient,
    ) {}

    /**
     * List workspace members enriched with user name/email from esporteam-auth.
     *
     * @param  array{role?:?string}  $filters
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function listByWorkspace(Workspace $workspace, array $filters, int $perPage = 25): array
    {
        $query = $workspace->members()->getQuery();

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        $paginator = $query->orderBy('created_at', 'asc')->paginate($perPage);

        $members = $paginator->getCollection();
        $userIds = $members->pluck('user_id')->map(fn ($id) => (int) $id)->all();

        $users = $this->authUserClient->lookup($userIds);

        $items = $members->map(function ($member) use ($users) {
            $userId = (int) $member->user_id;
            $user = $users[$userId] ?? null;

            return [
                'user_id'    => $userId,
                'name'       => $user['name'] ?? '(desconhecido)',
                'email'      => $user['email'] ?? '',
                'profile'    => $user['profile'] ?? 'user',
                'role'       => (string) $member->role,
                'created_at' => optional($member->created_at)->toIso8601String(),
            ];
        })->all();

        return [
            'items' => $items,
            'meta'  => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ];
    }
}
