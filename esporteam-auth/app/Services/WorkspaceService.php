<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WorkspaceService
{
    /**
     * Validates that the token holder is a member of the workspace.
     * Returns ['valid' => bool, 'is_owner' => bool].
     */
    public function validateMembership(string $workspaceId, string $token, int $userId): array
    {
        try {
            $response = Http::timeout(10)->withToken($token)
                ->get(config('services.workspace.url') . "/api/workspaces/{$workspaceId}");

            if ($response->status() !== 200) {
                return ['valid' => false, 'is_owner' => false];
            }

            $workspace = $response->json('data');
            $isOwner = (int) ($workspace['owner_id'] ?? 0) === $userId;

            return ['valid' => true, 'is_owner' => $isOwner];
        } catch (\Exception $e) {
            \Log::error('Failed to validate workspace membership', [
                'workspace_id' => $workspaceId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return ['valid' => false, 'is_owner' => false];
        }
    }

    /**
     * Returns FULL permissions (15 = CREATE|READ|UPDATE|DELETE) for all school modules.
     * Used for workspace owners who bypass the permissions table.
     */
    public function fullSchoolPermissions(): array
    {
        $modules = [
            'students', 'guardians', 'classrooms', 'enrollments', 'staff',
            'attendance', 'announcements', 'diary', 'menus', 'reports',
            'files', 'guardian_access', 'feed', 'agenda', 'chats', 'notifications',
        ];

        return array_fill_keys($modules, 15);
    }

    public function fetchSchoolPermissions(int $userId, string $workspaceId): ?array
    {
        try {
            $response = Http::timeout(10)
                ->withHeader('X-Service-Token', config('services.school.token'))
                ->get(config('services.school.url') . "/api/service/users/{$userId}/permissions", [
                    'workspace_id' => $workspaceId,
                ]);

            if ($response->successful()) {
                return $response->json('data.permissions') ?? [];
            }

            \Log::warning('Failed to fetch school permissions', [
                'user_id' => $userId,
                'workspace_id' => $workspaceId,
                'status' => $response->status(),
            ]);

            return [];
        } catch (\Exception $e) {
            \Log::error('Exception fetching school permissions', [
                'user_id' => $userId,
                'workspace_id' => $workspaceId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
