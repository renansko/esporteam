<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuditLogClient
{
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
            $url = rtrim((string) config('services.auth.url'), '/') . '/api/service/audit';

            $response = Http::withHeaders([
                'X-Service-Token' => (string) config('services.auth.service_token'),
                'Accept'          => 'application/json',
            ])->post($url, [
                'admin_user_id' => $adminId,
                'admin_email'   => $adminEmail,
                'action'        => $action,
                'target_type'   => $targetType,
                'target_id'     => $targetId,
                'snapshot'      => $snapshot,
                'metadata'      => $metadata,
            ]);

            if (!$response->successful()) {
                Log::error('AuditLogClient: non-successful response from esporteam-auth', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'action' => $action,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('AuditLogClient: failed to log audit entry', [
                'exception' => $e->getMessage(),
                'action'    => $action,
                'target_id' => $targetId,
            ]);
        }
    }
}
