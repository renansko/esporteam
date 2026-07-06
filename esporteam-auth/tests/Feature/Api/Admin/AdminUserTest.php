<?php

use App\Models\AdminAuditLog;
use App\Models\User;

describe('GET /api/admin/users', function () {
    beforeEach(function () {
        // O mockAuthUser usa User::first(), então o admin deve ser criado primeiro
        $this->admin = User::factory()->create(['permissions' => 2]);
    });

    describe('listagem paginada', function () {
        it('admin lista usuários com paginação', function () {
            // Arrange
            User::factory()->count(5)->create(['permissions' => 0]);

            // Act
            $response = $this->getJson('/api/admin/users');

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

            expect($response->json('data.meta.total'))->toBeGreaterThanOrEqual(6);
        });

        it('filtro email retorna apenas usuários cujo email contém o termo (LIKE)', function () {
            // Arrange
            User::factory()->create(['email' => 'joao@escola.com', 'permissions' => 0]);
            User::factory()->create(['email' => 'maria@outra.com', 'permissions' => 0]);

            // Act
            $response = $this->getJson('/api/admin/users?email=joao');

            // Assert
            $response->assertOk();
            $emails = collect($response->json('data.items'))->pluck('email');
            expect($emails)->toContain('joao@escola.com');
            expect($emails)->not->toContain('maria@outra.com');
        });

        it('filtro name retorna apenas usuários cujo nome contém o termo (LIKE)', function () {
            // Arrange
            User::factory()->create(['name' => 'Carlos Alberto', 'permissions' => 0]);
            User::factory()->create(['name' => 'Fernanda Lima', 'permissions' => 0]);

            // Act
            $response = $this->getJson('/api/admin/users?name=Carlos');

            // Assert
            $response->assertOk();
            $names = collect($response->json('data.items'))->pluck('name');
            expect($names)->toContain('Carlos Alberto');
            expect($names)->not->toContain('Fernanda Lima');
        });

        it('filtro is_admin=true retorna somente admins', function () {
            // Arrange
            User::factory()->create(['permissions' => 2]); // outro admin
            User::factory()->count(3)->create(['permissions' => 0]);

            // Act
            $response = $this->getJson('/api/admin/users?is_admin=true');

            // Assert
            $response->assertOk();
            $items = $response->json('data.items');
            foreach ($items as $user) {
                expect(($user['permissions'] & 2))->toBe(2);
            }
        });

        it('filtro is_admin=false retorna somente não-admins', function () {
            // Arrange
            User::factory()->count(3)->create(['permissions' => 0]);

            // Act
            $response = $this->getJson('/api/admin/users?is_admin=false');

            // Assert
            $response->assertOk();
            $items = $response->json('data.items');
            foreach ($items as $user) {
                expect(($user['permissions'] & 2))->toBe(0);
            }
        });
    });

    describe('detalhes de usuário', function () {
        it('admin consegue ver detalhes de outro user', function () {
            // Arrange
            $user = User::factory()->create(['permissions' => 0]);

            // Act
            $response = $this->getJson("/api/admin/users/{$user->id}");

            // Assert
            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'data'    => ['id' => $user->id, 'email' => $user->email],
                ]);
        });

        it('retorna 404 quando user não existe', function () {
            // Act
            $response = $this->getJson('/api/admin/users/99999');

            // Assert
            $response->assertNotFound()
                ->assertJson(['success' => false]);
        });
    });
});

describe('PUT /api/admin/users/{id}/permissions', function () {
    beforeEach(function () {
        // Editar permissões é restrito a owners (bit 2, valor 4). Owner implica admin → 6.
        $this->admin = User::factory()->create(['permissions' => 6]);
    });

    describe('happy path', function () {
        it('admin atualiza permissions de outro user', function () {
            // Arrange
            $target = User::factory()->create(['permissions' => 0]);

            // Act
            $response = $this->putJson("/api/admin/users/{$target->id}/permissions", [
                'permissions' => 3,
            ]);

            // Assert
            $response->assertOk()
                ->assertJson(['success' => true]);

            expect($target->fresh()->permissions)->toBe(3);
        });

        it('audit log é gravado com action update_permissions e metadata old/new', function () {
            // Arrange
            $target = User::factory()->create(['permissions' => 0]);

            // Act
            $this->putJson("/api/admin/users/{$target->id}/permissions", [
                'permissions' => 2,
            ])->assertOk();

            // Assert
            $log = AdminAuditLog::where('action', 'update_permissions')
                ->where('target_id', $target->id)
                ->first();

            expect($log)->not->toBeNull();
            expect($log->metadata['old'])->toBe(0);
            expect($log->metadata['new'])->toBe(2);
        });
    });

    describe('validações e erros', function () {
        it('user comum recebe 403 em GET /admin/users', function () {
            // Arrange — recria DB com user sem admin como primeiro
            User::query()->delete();
            User::factory()->create(['permissions' => 0]);

            // Act
            $response = $this->getJson('/api/admin/users');

            // Assert
            $response->assertForbidden()
                ->assertJson(['success' => false]);
        });

        it('user comum recebe 403 em GET /admin/users/{id}', function () {
            // Arrange
            User::query()->delete();
            $comum = User::factory()->create(['permissions' => 0]);

            // Act
            $response = $this->getJson("/api/admin/users/{$comum->id}");

            // Assert
            $response->assertForbidden();
        });

        it('user comum recebe 403 em PUT /admin/users/{id}/permissions', function () {
            // Arrange
            User::query()->delete();
            $comum = User::factory()->create(['permissions' => 0]);
            $target = User::factory()->create(['permissions' => 0]);

            // Act
            $response = $this->putJson("/api/admin/users/{$target->id}/permissions", [
                'permissions' => 2,
            ]);

            // Assert
            $response->assertForbidden();
        });

        it('retorna 422 quando permissions não é enviado', function () {
            // Arrange
            $target = User::factory()->create(['permissions' => 0]);

            // Act
            $response = $this->putJson("/api/admin/users/{$target->id}/permissions", []);

            // Assert
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['permissions']);
        });

        it('retorna 404 quando user alvo não existe', function () {
            // Act
            $response = $this->putJson('/api/admin/users/99999/permissions', [
                'permissions' => 2,
            ]);

            // Assert
            $response->assertNotFound();
        });
    });
});
