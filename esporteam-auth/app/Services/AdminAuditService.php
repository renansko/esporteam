<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminAuditService
{
    /**
     * Lists admin audit logs with optional filters.
     *
     * Supported filters:
     * - action (exact match)
     * - admin_user_id (int)
     * - admin_email (LIKE)
     * - target_type (exact match)
     * - target_id (int)
     * - from (datetime, created_at >=)
     * - to (datetime, created_at <=)
     */
    public function listLogs(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $perPage = max(1, min((int) $perPage, 100));

        $query = AdminAuditLog::query();

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['admin_user_id'])) {
            $query->where('admin_user_id', (int) $filters['admin_user_id']);
        }

        if (!empty($filters['admin_email'])) {
            $query->where('admin_email', 'like', '%' . $filters['admin_email'] . '%');
        }

        if (!empty($filters['target_type'])) {
            $query->where('target_type', $filters['target_type']);
        }

        if (!empty($filters['target_id'])) {
            $query->where('target_id', (int) $filters['target_id']);
        }

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
