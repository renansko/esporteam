<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    /**
     * Records an admin action in the audit log.
     * NEVER throws — failures are logged and swallowed so audit
     * cannot block primary business operations.
     */
    public function log(
        int $adminId,
        string $adminEmail,
        string $action,
        string $targetType,
        int $targetId,
        array $snapshot = [],
        array $metadata = []
    ): void {
        try {
            AdminAuditLog::create([
                'admin_user_id'   => $adminId,
                'admin_email'     => $adminEmail,
                'action'          => $action,
                'target_type'     => $targetType,
                'target_id'       => $targetId,
                'target_snapshot' => !empty($snapshot) ? $snapshot : null,
                'metadata'        => !empty($metadata) ? $metadata : null,
                'ip_address'      => request()?->ip(),
                'created_at'      => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to write admin audit log', [
                'admin_id'    => $adminId,
                'action'      => $action,
                'target_type' => $targetType,
                'target_id'   => $targetId,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Records an admin action in the audit log, propagating any failure.
     *
     * Use this variant for security-critical operations where the action
     * MUST NOT proceed if the audit trail cannot be written (e.g. impersonation).
     * For ordinary admin operations, prefer `log()` which is fail-safe.
     *
     * @throws \Throwable if the audit log write fails.
     */
    public function logOrFail(
        int $adminId,
        string $adminEmail,
        string $action,
        string $targetType,
        int $targetId,
        array $snapshot = [],
        array $metadata = []
    ): AdminAuditLog {
        return AdminAuditLog::create([
            'admin_user_id'   => $adminId,
            'admin_email'     => $adminEmail,
            'action'          => $action,
            'target_type'     => $targetType,
            'target_id'       => $targetId,
            'target_snapshot' => !empty($snapshot) ? $snapshot : null,
            'metadata'        => !empty($metadata) ? $metadata : null,
            'ip_address'      => request()?->ip(),
            'created_at'      => now(),
        ]);
    }
}
