<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceAuditController extends Controller
{
    public function __construct(private AuditLogService $auditLog) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'admin_user_id'   => ['required', 'integer'],
            'admin_email'    => ['required', 'string', 'email'],
            'action'         => ['required', 'string', 'max:255'],
            'target_type'    => ['required', 'string', 'max:255'],
            'target_id'      => ['required', 'integer'],
            'target_snapshot' => ['nullable', 'array'],
            'metadata'       => ['nullable', 'array'],
        ]);

        $this->auditLog->log(
            (int) $data['admin_user_id'],
            $data['admin_email'],
            $data['action'],
            $data['target_type'],
            (int) $data['target_id'],
            $data['target_snapshot'] ?? [],
            $data['metadata'] ?? [],
        );

        return $this->successResponse(null, __('messages.admin.audit.logged'));
    }
}
