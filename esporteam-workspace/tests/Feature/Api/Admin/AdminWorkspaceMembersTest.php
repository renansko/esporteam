<?php

use App\Http\Middleware\RequireEsporteamAdmin;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\Http;

// As rotas admin exigem auth.service + esporteam.admin.
// Em testes, auth.service faz bypass via APP_RUNNING_TESTS, mas o mock NÃO define is_esporteam_admin.
// Para os happy paths de admin usamos withoutMiddleware(RequireEsporteamAdmin::class).
// Para testar rejeição de não-admins, chamamos o middleware diretamente.

function fakeBulkLookup(array $users = []): void
{
    Http::fake([
        '*/service/users/bulk-lookup' => Http::response([
            'success' => true,
            'message' => 'ok',
            'data'    => $users,
        ], 200),
    ]);
}

describe('GET /api/admin/workspaces/{workspace}/members', function () {
    describe('happy path (admin)', function () {
        it('lista membros do workspace com paginação', function () {
            // Arrange
            fakeBulkLookup([
                ['id' => 10, 'name' => 'João',  'email' => 'joao@example.com'],
                ['id' => 11, 'name' => 'Maria', 'email' => 'maria@example.com'],
            ]);
            $workspace = Workspace::factory()->create();
            WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => 10, 'role' => 'owner']);
            WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => 11, 'role' => 'member']);

            // Act
            $response = $this->withoutMiddleware(RequireEsporteamAdmin::class)
                ->getJson("/api/admin/workspaces/{$workspace->id}/members");

            // Assert
            $response->assertOk()
                ->assertJson(['success' => true])
                ->assertJsonStructure([
                    'data' => [
                        'items' => [['user_id', 'name', 'email', 'role', 'created_at']],
                        'meta'  => ['current_page', 'per_page', 'total', 'last_page'],
                    ],
                ]);

            $items = $response->json('data.items');
            expect($items)->toHaveCount(2);
            expect($items[0]['user_id'])->toBe(10);
            expect($items[0]['name'])->toBe('João');
            expect($items[0]['email'])->toBe('joao@example.com');
            expect($items[0]['role'])->toBe('owner');
        });

        it('filtro role retorna apenas membros com aquela role', function () {
            // Arrange
            fakeBulkLookup([
                ['id' => 20, 'name' => 'Ana',   'email' => 'ana@example.com'],
                ['id' => 21, 'name' => 'Bruno', 'email' => 'bruno@example.com'],
            ]);
            $workspace = Workspace::factory()->create();
            WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => 20, 'role' => 'owner']);
            WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => 21, 'role' => 'member']);

            // Act
            $response = $this->withoutMiddleware(RequireEsporteamAdmin::class)
                ->getJson("/api/admin/workspaces/{$workspace->id}/members?role=owner");

            // Assert
            $response->assertOk();
            $items = $response->json('data.items');
            expect($items)->toHaveCount(1);
            expect($items[0]['role'])->toBe('owner');
            expect($items[0]['user_id'])->toBe(20);
        });

        it('usa placeholder quando bulk-lookup retorna vazio', function () {
            // Arrange
            fakeBulkLookup([]);
            $workspace = Workspace::factory()->create();
            WorkspaceMember::create(['workspace_id' => $workspace->id, 'user_id' => 30, 'role' => 'owner']);

            // Act
            $response = $this->withoutMiddleware(RequireEsporteamAdmin::class)
                ->getJson("/api/admin/workspaces/{$workspace->id}/members");

            // Assert
            $response->assertOk();
            $items = $response->json('data.items');
            expect($items)->toHaveCount(1);
            expect($items[0]['user_id'])->toBe(30);
            expect($items[0]['name'])->toBe('(desconhecido)');
            expect($items[0]['email'])->toBe('');
        });

        it('paginação meta é correta', function () {
            // Arrange
            fakeBulkLookup([]);
            $workspace = Workspace::factory()->create();
            for ($i = 1; $i <= 5; $i++) {
                WorkspaceMember::create([
                    'workspace_id' => $workspace->id,
                    'user_id'      => 100 + $i,
                    'role'         => 'member',
                ]);
            }

            // Act
            $response = $this->withoutMiddleware(RequireEsporteamAdmin::class)
                ->getJson("/api/admin/workspaces/{$workspace->id}/members?per_page=2&page=2");

            // Assert
            $response->assertOk();
            $meta = $response->json('data.meta');
            expect($meta['current_page'])->toBe(2);
            expect($meta['per_page'])->toBe(2);
            expect($meta['total'])->toBe(5);
            expect($meta['last_page'])->toBe(3);
            expect($response->json('data.items'))->toHaveCount(2);
        });
    });

    describe('acesso negado', function () {
        it('user comum (sem is_esporteam_admin) recebe 403', function () {
            // Arrange — verifica o middleware diretamente, sem bypass
            $middleware = new RequireEsporteamAdmin();
            $request = \Illuminate\Http\Request::create('/api/admin/workspaces/1/members', 'GET');

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
