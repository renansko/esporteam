<?php

use App\Http\Middleware\RequireEsporteamAdmin;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;

// As rotas admin exigem auth.service + esporteam.admin.
// Em testes, auth.service faz bypass via APP_RUNNING_TESTS, mas o mock NÃO define is_esporteam_admin.
// Para os happy paths de admin usamos withoutMiddleware(RequireEsporteamAdmin::class).
// Para testar rejeição de não-admins, chamamos o middleware diretamente.

describe('GET /api/admin/workspaces', function () {
    describe('happy path (admin)', function () {
        it('lista workspaces com paginação e members_count', function () {
            // Arrange
            Workspace::factory()->count(3)->create(['is_active' => true]);

            // Act
            $response = $this->withoutMiddleware(RequireEsporteamAdmin::class)
                ->getJson('/api/admin/workspaces');

            // Assert
            $response->assertOk()
                ->assertJson(['success' => true]);

            $data = $response->json('data');
            expect($data)->toHaveKey('items'); // response usa {items, meta}
        });

        it('filtro name retorna apenas workspaces cujo nome contém o termo', function () {
            // Arrange
            Workspace::factory()->create(['name' => 'Escola Girassol', 'slug' => 'escola-girassol']);
            Workspace::factory()->create(['name' => 'Creche Margarida', 'slug' => 'creche-margarida']);

            // Act
            $response = $this->withoutMiddleware(RequireEsporteamAdmin::class)
                ->getJson('/api/admin/workspaces?name=Girassol');

            // Assert
            $response->assertOk();
            $items = $response->json('data.items');
            $names = collect($items)->pluck('name');
            expect($names)->toContain('Escola Girassol');
            expect($names)->not->toContain('Creche Margarida');
        });

        it('filtro slug funciona', function () {
            // Arrange
            Workspace::factory()->create(['name' => 'Escola A', 'slug' => 'escola-aaaa']);
            Workspace::factory()->create(['name' => 'Escola B', 'slug' => 'escola-bbbb']);

            // Act
            $response = $this->withoutMiddleware(RequireEsporteamAdmin::class)
                ->getJson('/api/admin/workspaces?slug=aaaa');

            // Assert
            $response->assertOk();
            $items = $response->json('data.items');
            $slugs = collect($items)->pluck('slug');
            expect($slugs)->toContain('escola-aaaa');
            expect($slugs)->not->toContain('escola-bbbb');
        });

        it('filtro is_active=true retorna apenas workspaces ativos', function () {
            // Arrange
            Workspace::factory()->create(['name' => 'Ativo', 'slug' => 'ativo-1', 'is_active' => true]);
            Workspace::factory()->create(['name' => 'Inativo', 'slug' => 'inativo-1', 'is_active' => false]);

            // Act
            $response = $this->withoutMiddleware(RequireEsporteamAdmin::class)
                ->getJson('/api/admin/workspaces?is_active=true');

            // Assert
            $response->assertOk();
            $items = $response->json('data.items');
            foreach ($items as $item) {
                expect($item['is_active'])->toBeTrue();
            }
        });

        it('filtro is_active=false retorna apenas workspaces inativos', function () {
            // Arrange
            Workspace::factory()->create(['name' => 'Ativo2', 'slug' => 'ativo-2', 'is_active' => true]);
            Workspace::factory()->create(['name' => 'Inativo2', 'slug' => 'inativo-2', 'is_active' => false]);

            // Act
            $response = $this->withoutMiddleware(RequireEsporteamAdmin::class)
                ->getJson('/api/admin/workspaces?is_active=false');

            // Assert
            $response->assertOk();
            $items = $response->json('data.items');
            foreach ($items as $item) {
                expect($item['is_active'])->toBeFalse();
            }
        });
    });

    describe('acesso negado', function () {
        it('user comum (sem is_esporteam_admin) recebe 403', function () {
            // Arrange — verifica o middleware diretamente, sem bypass
            $middleware = new RequireEsporteamAdmin();
            $request = \Illuminate\Http\Request::create('/api/admin/workspaces', 'GET');

            // Usuário sem is_esporteam_admin
            $user = (object) ['id' => 1, 'email' => 'comum@test.com', 'is_esporteam_admin' => false];
            $request->setUserResolver(fn () => $user);

            // Act
            $result = $middleware->handle($request, fn ($r) => response()->json(['ok' => true]));

            // Assert
            expect($result->getStatusCode())->toBe(403);
            $body = json_decode($result->getContent(), true);
            expect($body['success'])->toBeFalse();
        });
    });
});

describe('GET /api/admin/workspaces/{workspace}', function () {
    it('admin vê detalhes de workspace com stats', function () {
        // Arrange
        $workspace = Workspace::factory()->create(['is_active' => true]);

        // Act
        $response = $this->withoutMiddleware(RequireEsporteamAdmin::class)
            ->getJson("/api/admin/workspaces/{$workspace->id}");

        // Assert
        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug', 'is_active', 'members_count'],
            ]);
    });
});

describe('PATCH /api/admin/workspaces/{workspace}/status', function () {
    describe('happy path', function () {
        it('admin desativa workspace via PATCH com active=false', function () {
            // Arrange
            Http::fake(['*' => Http::response(['success' => true], 200)]);
            $workspace = Workspace::factory()->create(['is_active' => true]);

            // Act
            $response = $this->withoutMiddleware(RequireEsporteamAdmin::class)
                ->patchJson("/api/admin/workspaces/{$workspace->id}/status", ['active' => false]);

            // Assert
            $response->assertOk()
                ->assertJson(['success' => true]);

            expect($workspace->fresh()->is_active)->toBeFalse();
        });

        it('admin reativa workspace via PATCH com active=true', function () {
            // Arrange
            Http::fake(['*' => Http::response(['success' => true], 200)]);
            $workspace = Workspace::factory()->create(['is_active' => false]);

            // Act
            $response = $this->withoutMiddleware(RequireEsporteamAdmin::class)
                ->patchJson("/api/admin/workspaces/{$workspace->id}/status", ['active' => true]);

            // Assert
            $response->assertOk()
                ->assertJson(['success' => true]);

            expect($workspace->fresh()->is_active)->toBeTrue();
        });

        it('AuditLogClient envia request HTTP com X-Service-Token e body correto ao esporteam-auth', function () {
            // Arrange
            Http::fake(['*' => Http::response(['success' => true], 200)]);
            config([
                'services.auth.url'           => 'http://esporteam-auth',
                'services.auth.service_token' => 'test-service-token',
            ]);
            $workspace = Workspace::factory()->create(['is_active' => true, 'name' => 'Escola Teste', 'slug' => 'escola-teste']);

            // Act
            $this->withoutMiddleware(RequireEsporteamAdmin::class)
                ->patchJson("/api/admin/workspaces/{$workspace->id}/status", ['active' => false]);

            // Assert — verifica que HTTP foi chamado para o esporteam-auth
            Http::assertSent(function ($request) use ($workspace) {
                return str_contains($request->url(), '/api/service/audit')
                    && $request->hasHeader('X-Service-Token', 'test-service-token')
                    && $request->data()['action'] === 'deactivate_workspace'
                    && $request->data()['target_id'] === $workspace->id;
            });
        });
    });

    describe('validações', function () {
        it('retorna 422 quando active não é enviado', function () {
            // Arrange
            Http::fake(['*' => Http::response(['success' => true], 200)]);
            $workspace = Workspace::factory()->create();

            // Act
            $response = $this->withoutMiddleware(RequireEsporteamAdmin::class)
                ->patchJson("/api/admin/workspaces/{$workspace->id}/status", []);

            // Assert
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['active']);
        });
    });
});
