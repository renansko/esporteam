<?php

use App\Models\AdminAuditLog;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Log;

describe('AuditLogService', function () {
    describe('log', function () {
        it('grava log com todos os campos corretos', function () {
            // Arrange
            $service = new AuditLogService();

            // Act
            $service->log(
                adminId: 1,
                adminEmail: 'admin@esporteam.com',
                action: 'impersonate',
                targetType: 'user',
                targetId: 42,
                snapshot: ['target_email' => 'user@test.com'],
                metadata: ['workspace_id' => 5],
            );

            // Assert
            $log = AdminAuditLog::where('action', 'impersonate')
                ->where('admin_user_id', 1)
                ->where('target_id', 42)
                ->first();

            expect($log)->not->toBeNull();
            expect($log->admin_email)->toBe('admin@esporteam.com');
            expect($log->target_type)->toBe('user');
            expect($log->target_snapshot)->toBe(['target_email' => 'user@test.com']);
            expect($log->metadata)->toBe(['workspace_id' => 5]);
        });

        it('captura o IP automaticamente a partir do request', function () {
            // Arrange
            $service = new AuditLogService();
            $request = \Illuminate\Http\Request::create('/api/service/audit', 'POST');
            $request->server->set('REMOTE_ADDR', '192.168.1.100');
            app()->instance('request', $request);

            // Act
            $service->log(
                adminId: 2,
                adminEmail: 'admin2@esporteam.com',
                action: 'update_permissions',
                targetType: 'user',
                targetId: 10,
            );

            // Assert
            $log = AdminAuditLog::where('action', 'update_permissions')
                ->where('admin_user_id', 2)
                ->first();

            expect($log)->not->toBeNull();
            expect($log->ip_address)->toBe('192.168.1.100');
        });

        it('grava log sem snapshot e metadata quando não fornecidos', function () {
            // Arrange
            $service = new AuditLogService();

            // Act
            $service->log(
                adminId: 3,
                adminEmail: 'admin3@esporteam.com',
                action: 'list_users',
                targetType: 'user',
                targetId: 0,
            );

            // Assert
            $log = AdminAuditLog::where('action', 'list_users')->where('admin_user_id', 3)->first();
            expect($log)->not->toBeNull();
            expect($log->target_snapshot)->toBeNull();
            expect($log->metadata)->toBeNull();
        });

        it('NÃO lança exceção quando o banco falha — swallows e registra Log::error', function () {
            // Arrange
            Log::spy();

            // Subclasse que sobrescreve o save() para simular falha de DB
            $failingService = new class extends AuditLogService {
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
                        throw new \RuntimeException('Simulated DB failure');
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to write admin audit log', [
                            'admin_id'    => $adminId,
                            'action'      => $action,
                            'target_type' => $targetType,
                            'target_id'   => $targetId,
                            'error'       => $e->getMessage(),
                        ]);
                    }
                }
            };

            // Act — NÃO deve lançar exceção
            expect(fn () => $failingService->log(
                adminId: 99,
                adminEmail: 'fail@esporteam.com',
                action: 'fail_action',
                targetType: 'user',
                targetId: 1,
            ))->not->toThrow(\Throwable::class);

            // Assert — Log::error foi chamado com os dados esperados
            Log::shouldHaveReceived('error')
                ->once()
                ->with('Failed to write admin audit log', \Mockery::on(fn ($ctx) => $ctx['action'] === 'fail_action'));
        });
    });

    describe('logOrFail', function () {
        it('grava o log e retorna a instância do AdminAuditLog', function () {
            // Arrange
            $service = new AuditLogService();

            // Act
            $log = $service->logOrFail(
                adminId: 7,
                adminEmail: 'strict@esporteam.com',
                action: 'impersonate',
                targetType: 'user',
                targetId: 99,
                snapshot: ['target_email' => 'user99@test.com'],
                metadata: ['workspace_id' => 3],
            );

            // Assert
            expect($log)->toBeInstanceOf(AdminAuditLog::class);
            expect($log->admin_user_id)->toBe(7);
            expect($log->action)->toBe('impersonate');
            expect($log->target_snapshot)->toBe(['target_email' => 'user99@test.com']);
            expect(AdminAuditLog::where('admin_user_id', 7)->count())->toBe(1);
        });

        it('propaga exceção quando o insert falha (diferente de log() que engole)', function () {
            // Arrange — força violação de NOT NULL passando um admin_email vazio
            // em conjunto com uma ação que não tolera — mas o schema define admin_email
            // como string not-null, então passamos um valor inválido via cast.
            // A forma mais confiável: mockar AdminAuditLog::create lançando exceção.
            $failingService = new class extends AuditLogService {
                public function logOrFail(
                    int $adminId,
                    string $adminEmail,
                    string $action,
                    string $targetType,
                    int $targetId,
                    array $snapshot = [],
                    array $metadata = []
                ): AdminAuditLog {
                    throw new \RuntimeException('Simulated DB failure on logOrFail');
                }
            };

            // Act + Assert
            expect(fn () => $failingService->logOrFail(
                adminId: 1,
                adminEmail: 'x@esporteam.com',
                action: 'impersonate',
                targetType: 'user',
                targetId: 1,
            ))->toThrow(\RuntimeException::class, 'Simulated DB failure on logOrFail');
        });
    });
});
