<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function __construct(private AuditLogService $auditLog) {}

    /**
     * Lists users with optional filters (email, name, is_admin).
     */
    public function listUsers(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = max(1, min((int) $perPage, 100));

        $query = User::query();

        if (!empty($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }

        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (array_key_exists('is_admin', $filters) && $filters['is_admin'] !== null && $filters['is_admin'] !== '') {
            $isAdmin = filter_var($filters['is_admin'], FILTER_VALIDATE_BOOLEAN);
            if ($isAdmin) {
                $query->whereRaw('(permissions & 2) = 2');
            } else {
                $query->whereRaw('(permissions & 2) = 0');
            }
        }

        if (array_key_exists('is_owner', $filters) && $filters['is_owner'] !== null && $filters['is_owner'] !== '') {
            $isOwner = filter_var($filters['is_owner'], FILTER_VALIDATE_BOOLEAN);
            if ($isOwner) {
                $query->whereRaw('(permissions & 4) = 4');
            } else {
                $query->whereRaw('(permissions & 4) = 0');
            }
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function getUser(int $id): User
    {
        return User::findOrFail($id);
    }

    /**
     * Returns a list of users matching the provided ids as plain arrays
     * containing only id, name, email and profile. Missing ids are simply omitted.
     *
     * @param  array<int, int>  $ids
     * @return array<int, array{id:int,name:string,email:string,profile:string}>
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return User::whereIn('id', $ids)
            ->get(['id', 'name', 'email', 'profile'])
            ->map(fn ($u) => [
                'id'      => (int) $u->id,
                'name'    => (string) $u->name,
                'email'   => (string) $u->email,
                'profile' => (string) $u->profile,
            ])
            ->all();
    }

    /**
     * Service-to-service grant of permissions. Used by other esporteam services
     * (notably the E2E test harness) to bootstrap an esporteam_admin without
     * requiring an existing owner JWT. Bit 2 (`is_esporteam_owner`) is rejected —
     * ownership must still go through the artisan `esporteam:grant-owner` bootstrap.
     */
    public function grantPermissionsViaService(int $targetUserId, int $permissions): User
    {
        if ($permissions < 0) {
            throw new \InvalidArgumentException('Permissions must be a non-negative integer');
        }
        if (($permissions & 4) === 4) {
            throw new \InvalidArgumentException('Owner bit (4) cannot be granted via service token; use esporteam:grant-owner');
        }

        $target = User::findOrFail($targetUserId);
        $old = (int) $target->permissions;

        $target->update(['permissions' => $permissions]);

        $this->auditLog->log(
            0,
            'service',
            'grant_permissions_via_service',
            'user',
            (int) $target->id,
            ['target_email' => $target->email],
            ['old' => $old, 'new' => $permissions],
        );

        return $target->fresh();
    }

    /**
     * Updates a target user's permissions bitmask, logging the change.
     */
    public function updatePermissions(User $admin, int $targetId, int $permissions): User
    {
        if ($permissions < 0) {
            throw new \InvalidArgumentException('Permissions must be a non-negative integer');
        }

        $target = User::findOrFail($targetId);
        $old = (int) $target->permissions;

        $target->update(['permissions' => $permissions]);

        $this->auditLog->log(
            (int) $admin->id,
            (string) $admin->email,
            'update_permissions',
            'user',
            (int) $target->id,
            ['target_email' => $target->email],
            ['old' => $old, 'new' => $permissions],
        );

        return $target->fresh();
    }
}
