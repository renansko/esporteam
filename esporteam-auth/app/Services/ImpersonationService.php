<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImpersonationService
{
    public function __construct(
        private JwtService $jwt,
        private WorkspaceService $workspace,
        private AuditLogService $auditLog,
    ) {}

    /**
     * Creates a short-lived impersonation token for the given target user.
     *
     * @return array{token:string,expires_at:string,user:User}
     */
    public function impersonate(User $admin, int $targetUserId, ?int $workspaceId = null): array
    {
        $target = User::findOrFail($targetUserId);

        // Block impersonation of admins (bit 1) or owners (bit 2).
        if ((((int) $target->permissions) & 6) !== 0) {
            throw ValidationException::withMessages([
                'user_id' => [__('messages.admin.impersonation.cannot_impersonate_admin')],
            ]);
        }

        $isOwner = null;
        $schoolPermissions = null;

        if ($workspaceId !== null) {
            // Admin flag is already validated by middleware; grant full context.
            $isOwner = false;
            $schoolPermissions = $this->workspace->fetchSchoolPermissions($target->id, (string) $workspaceId);

            if (empty($schoolPermissions)) {
                // Target has no explicit permissions — fall back to empty array so token remains valid
                // (admin context is the source of access here, not target membership).
                $schoolPermissions = [];
            }
        }

        // SECURITY: audit log must be written BEFORE the token is generated.
        // `logOrFail` propagates exceptions so that if the audit trail cannot be
        // persisted, the impersonation is aborted and NO token is issued.
        $this->auditLog->logOrFail(
            (int) $admin->id,
            (string) $admin->email,
            'impersonate',
            'user',
            (int) $target->id,
            ['target_email' => $target->email],
            ['workspace_id' => $workspaceId],
        );

        $token = $this->jwt->encodeImpersonation(
            $target->toArray(),
            (int) $admin->id,
            $workspaceId,
            $isOwner,
            $schoolPermissions,
        );

        return [
            'token'      => $token,
            'expires_at' => now()->addHour()->toIso8601String(),
            'user'       => $target,
        ];
    }
}
