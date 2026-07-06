<?php

use App\Models\AdminAuditLog;
use App\Models\User;

describe('GET /api/admin/audit', function () {
    beforeEach(function () {
        // O mockAuthUser usa User::first(), então o admin deve ser criado primeiro
        $this->admin = User::factory()->create(['permissions' => 2]);
    });

    describe('listagem paginada', function () {
        it('admin lista audit logs com paginação', function () {
            // Arrange
            for ($i = 0; $i < 3; $i++) {
                AdminAuditLog::create([
                    'admin_user_id' => $this->admin->id,
                    'admin_email'   => $this->admin->email,
                    'action'        => 'update_permissions',
                    'target_type'   => 'user',
                    'target_id'     => 10 + $i,
                    'created_at'    => now(),
                ]);
            }

            // Act
            $response = $this->getJson('/api/admin/audit');

            // Assert
            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'items',
                        'meta' => ['current_page', 'per_page', 'total', 'last_page'],
                    ],
                ])
                ->assertJson(['success' => true]);

            expect($response->json('data.meta.total'))->toBeGreaterThanOrEqual(3);
        });

        it('filtro action retorna somente logs com action exata', function () {
            // Arrange
            AdminAuditLog::create([
                'admin_user_id' => $this->admin->id,
                'admin_email'   => $this->admin->email,
                'action'        => 'update_permissions',
                'target_type'   => 'user',
                'target_id'     => 1,
                'created_at'    => now(),
            ]);
            AdminAuditLog::create([
                'admin_user_id' => $this->admin->id,
                'admin_email'   => $this->admin->email,
                'action'        => 'impersonate',
                'target_type'   => 'user',
                'target_id'     => 2,
                'created_at'    => now(),
            ]);

            // Act
            $response = $this->getJson('/api/admin/audit?action=impersonate');

            // Assert
            $response->assertOk();
            $items = $response->json('data.items');
            expect($items)->not->toBeEmpty();
            foreach ($items as $log) {
                expect($log['action'])->toBe('impersonate');
            }
        });

        it('filtro admin_user_id retorna somente logs daquele admin', function () {
            // Arrange
            $otherAdmin = User::factory()->create(['permissions' => 2]);
            AdminAuditLog::create([
                'admin_user_id' => $this->admin->id,
                'admin_email'   => $this->admin->email,
                'action'        => 'update_permissions',
                'target_type'   => 'user',
                'target_id'     => 1,
                'created_at'    => now(),
            ]);
            AdminAuditLog::create([
                'admin_user_id' => $otherAdmin->id,
                'admin_email'   => $otherAdmin->email,
                'action'        => 'update_permissions',
                'target_type'   => 'user',
                'target_id'     => 2,
                'created_at'    => now(),
            ]);

            // Act
            $response = $this->getJson("/api/admin/audit?admin_user_id={$otherAdmin->id}");

            // Assert
            $response->assertOk();
            $items = $response->json('data.items');
            expect($items)->not->toBeEmpty();
            foreach ($items as $log) {
                expect($log['admin_user_id'])->toBe($otherAdmin->id);
            }
        });

        it('filtro target_type retorna somente logs com aquele target_type', function () {
            // Arrange
            AdminAuditLog::create([
                'admin_user_id' => $this->admin->id,
                'admin_email'   => $this->admin->email,
                'action'        => 'update_permissions',
                'target_type'   => 'user',
                'target_id'     => 1,
                'created_at'    => now(),
            ]);
            AdminAuditLog::create([
                'admin_user_id' => $this->admin->id,
                'admin_email'   => $this->admin->email,
                'action'        => 'update_student',
                'target_type'   => 'student',
                'target_id'     => 1,
                'created_at'    => now(),
            ]);

            // Act
            $response = $this->getJson('/api/admin/audit?target_type=student');

            // Assert
            $response->assertOk();
            $items = $response->json('data.items');
            expect($items)->not->toBeEmpty();
            foreach ($items as $log) {
                expect($log['target_type'])->toBe('student');
            }
        });

        it('filtro from/to restringe ao período informado', function () {
            // Arrange
            AdminAuditLog::create([
                'admin_user_id' => $this->admin->id,
                'admin_email'   => $this->admin->email,
                'action'        => 'update_permissions',
                'target_type'   => 'user',
                'target_id'     => 1,
                'created_at'    => '2026-01-01 10:00:00',
            ]);
            AdminAuditLog::create([
                'admin_user_id' => $this->admin->id,
                'admin_email'   => $this->admin->email,
                'action'        => 'update_permissions',
                'target_type'   => 'user',
                'target_id'     => 2,
                'created_at'    => '2026-03-15 10:00:00',
            ]);
            AdminAuditLog::create([
                'admin_user_id' => $this->admin->id,
                'admin_email'   => $this->admin->email,
                'action'        => 'update_permissions',
                'target_type'   => 'user',
                'target_id'     => 3,
                'created_at'    => '2026-06-01 10:00:00',
            ]);

            // Act
            $response = $this->getJson('/api/admin/audit?from=2026-02-01&to=2026-04-01');

            // Assert
            $response->assertOk();
            $ids = collect($response->json('data.items'))->pluck('target_id');
            expect($ids)->toContain(2);
            expect($ids)->not->toContain(1);
            expect($ids)->not->toContain(3);
        });

        it('ordenação é DESC por created_at (mais recentes primeiro)', function () {
            // Arrange
            AdminAuditLog::create([
                'admin_user_id' => $this->admin->id,
                'admin_email'   => $this->admin->email,
                'action'        => 'update_permissions',
                'target_type'   => 'user',
                'target_id'     => 1,
                'created_at'    => '2026-01-01 10:00:00',
            ]);
            AdminAuditLog::create([
                'admin_user_id' => $this->admin->id,
                'admin_email'   => $this->admin->email,
                'action'        => 'update_permissions',
                'target_type'   => 'user',
                'target_id'     => 2,
                'created_at'    => '2026-04-01 10:00:00',
            ]);
            AdminAuditLog::create([
                'admin_user_id' => $this->admin->id,
                'admin_email'   => $this->admin->email,
                'action'        => 'update_permissions',
                'target_type'   => 'user',
                'target_id'     => 3,
                'created_at'    => '2026-02-01 10:00:00',
            ]);

            // Act
            $response = $this->getJson('/api/admin/audit');

            // Assert
            $response->assertOk();
            $items = collect($response->json('data.items'));
            $timestamps = $items->pluck('created_at')->values()->all();
            $sorted = $timestamps;
            rsort($sorted);
            expect($timestamps)->toBe($sorted);
        });

        it('per_page acima de 100 é capped em 100', function () {
            // Act
            $response = $this->getJson('/api/admin/audit?per_page=500');

            // Assert — validação rejeita >100 com 422
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['per_page']);
        });
    });

    describe('autorização', function () {
        it('user comum recebe 403 em GET /admin/audit', function () {
            // Arrange — recria DB com user sem admin como primeiro
            User::query()->delete();
            User::factory()->create(['permissions' => 0]);

            // Act
            $response = $this->getJson('/api/admin/audit');

            // Assert
            $response->assertForbidden()
                ->assertJson(['success' => false]);
        });
    });
});
