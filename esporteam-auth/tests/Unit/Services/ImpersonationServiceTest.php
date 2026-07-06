<?php

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\ImpersonationService;
use App\Services\JwtService;
use App\Services\WorkspaceService;

describe('ImpersonationService', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['permissions' => 2]);
        $this->target = User::factory()->create(['permissions' => 0]);
    });

    describe('audit log ordering (security)', function () {
        it('writes the audit log BEFORE generating the token', function () {
            // Arrange — spies that record call order
            $callOrder = [];

            $audit = Mockery::mock(AuditLogService::class);
            $audit->shouldReceive('logOrFail')
                ->once()
                ->andReturnUsing(function () use (&$callOrder) {
                    $callOrder[] = 'audit';
                    return new AdminAuditLog();
                });

            $jwt = Mockery::mock(JwtService::class);
            $jwt->shouldReceive('encodeImpersonation')
                ->once()
                ->andReturnUsing(function () use (&$callOrder) {
                    $callOrder[] = 'jwt';
                    return 'fake-token';
                });

            $workspace = Mockery::mock(WorkspaceService::class);

            $service = new ImpersonationService($jwt, $workspace, $audit);

            // Act
            $service->impersonate($this->admin, $this->target->id);

            // Assert — audit must be the first recorded call
            expect($callOrder)->toBe(['audit', 'jwt']);
        });

        it('aborts impersonation and issues NO token when the audit log write fails', function () {
            // Arrange
            $audit = Mockery::mock(AuditLogService::class);
            $audit->shouldReceive('logOrFail')
                ->once()
                ->andThrow(new RuntimeException('DB unavailable'));

            $jwt = Mockery::mock(JwtService::class);
            // CRITICAL: encodeImpersonation must NEVER be called if audit fails.
            $jwt->shouldNotReceive('encodeImpersonation');

            $workspace = Mockery::mock(WorkspaceService::class);

            $service = new ImpersonationService($jwt, $workspace, $audit);

            // Act + Assert — exception must propagate
            expect(fn () => $service->impersonate($this->admin, $this->target->id))
                ->toThrow(RuntimeException::class, 'DB unavailable');
        });

        it('does not create any AdminAuditLog row when token generation would have been blocked', function () {
            // Arrange
            $audit = Mockery::mock(AuditLogService::class);
            $audit->shouldReceive('logOrFail')
                ->once()
                ->andThrow(new RuntimeException('DB unavailable'));

            $jwt = Mockery::mock(JwtService::class);
            $workspace = Mockery::mock(WorkspaceService::class);

            $service = new ImpersonationService($jwt, $workspace, $audit);

            // Act
            try {
                $service->impersonate($this->admin, $this->target->id);
            } catch (\Throwable) {
                // expected
            }

            // Assert — no audit row persisted
            expect(AdminAuditLog::where('action', 'impersonate')->count())->toBe(0);
        });
    });

    afterEach(function () {
        Mockery::close();
    });
});
