<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAuditLogsRequest;
use App\Services\AdminAuditService;
use Illuminate\Http\JsonResponse;

class AdminAuditController extends Controller
{
    public function __construct(private AdminAuditService $adminAudit) {}

    public function index(ListAuditLogsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 25);

        $paginator = $this->adminAudit->listLogs($filters, $perPage);

        return $this->successResponse([
            'items' => $paginator->items(),
            'meta'  => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ], __('messages.admin.audit.listed'));
    }
}
