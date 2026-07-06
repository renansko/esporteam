<?php

use App\Models\Workspace;

// O mock do AuthenticateViaAuthService não define is_esporteam_admin no user.
// As rotas sob workspace.active bloqueiam workspaces inativos para users comuns.
// Testamos via HTTP real — o mock gera user sem is_esporteam_admin, simulando user comum.

describe('CheckWorkspaceActive — via rota HTTP', function () {
    describe('workspace desativado', function () {
        it('user comum recebe 403 com error_code workspace_deactivated ao acessar workspace inativo', function () {
            // Arrange
            $workspace = Workspace::factory()->create(['is_active' => false]);

            // Act — o mock do auth.service gera user sem is_esporteam_admin (user comum por padrão)
            $response = $this->getJson("/api/workspaces/{$workspace->id}");

            // Assert
            $response->assertForbidden()
                ->assertJson([
                    'success'    => false,
                    'error_code' => 'workspace_deactivated',
                ]);
        });
    });

    describe('workspace ativo', function () {
        it('workspace ativo é acessível normalmente por user comum', function () {
            // Arrange
            $workspace = Workspace::factory()->create(['is_active' => true]);
            // O mock de auth define user_id=1 — precisa ser membro para passar o AuthorizesWorkspace
            \App\Models\WorkspaceMember::create([
                'workspace_id' => $workspace->id,
                'user_id'      => 1,
                'role'         => 'member',
            ]);

            // Act
            $response = $this->getJson("/api/workspaces/{$workspace->id}");

            // Assert
            $response->assertOk()
                ->assertJson(['success' => true]);
        });
    });
});

describe('CheckWorkspaceActive — middleware direto', function () {
    it('user sem is_esporteam_admin é bloqueado para workspace inativo', function () {
        // Arrange
        $workspace = Workspace::factory()->create(['is_active' => false]);

        $middleware = new \App\Http\Middleware\CheckWorkspaceActive();
        $request = \Illuminate\Http\Request::create("/api/workspaces/{$workspace->id}", 'GET');

        // Passa o workspace via route binding simulado
        $request->setRouteResolver(function () use ($workspace, $request) {
            $route = new \Illuminate\Routing\Route('GET', '/api/workspaces/{workspace}', []);
            $route->bind($request);
            $route->setParameter('workspace', $workspace);
            return $route;
        });

        $user = (object) ['id' => 1, 'email' => 'user@test.com', 'is_esporteam_admin' => false, 'workspace_id' => null];
        $request->setUserResolver(fn () => $user);

        // Act
        $result = $middleware->handle($request, fn ($r) => response()->json(['ok' => true], 200));

        // Assert
        expect($result->getStatusCode())->toBe(403);
        $body = json_decode($result->getContent(), true);
        expect($body['error_code'])->toBe('workspace_deactivated');
    });

    it('admin (is_esporteam_admin=true) faz bypass mesmo com workspace inativo', function () {
        // Arrange
        $workspace = Workspace::factory()->create(['is_active' => false]);

        $middleware = new \App\Http\Middleware\CheckWorkspaceActive();
        $request = \Illuminate\Http\Request::create("/api/workspaces/{$workspace->id}", 'GET');

        $request->setRouteResolver(function () use ($workspace, $request) {
            $route = new \Illuminate\Routing\Route('GET', '/api/workspaces/{workspace}', []);
            $route->bind($request);
            $route->setParameter('workspace', $workspace);
            return $route;
        });

        // Admin com is_esporteam_admin true
        $user = (object) ['id' => 1, 'email' => 'admin@esporteam.com', 'is_esporteam_admin' => true, 'workspace_id' => null];
        $request->setUserResolver(fn () => $user);

        // Act
        $result = $middleware->handle($request, fn ($r) => response()->json(['ok' => true], 200));

        // Assert — deve passar sem bloqueio
        expect($result->getStatusCode())->toBe(200);
    });

    it('workspace ativo passa normalmente', function () {
        // Arrange
        $workspace = Workspace::factory()->create(['is_active' => true]);

        $middleware = new \App\Http\Middleware\CheckWorkspaceActive();
        $request = \Illuminate\Http\Request::create("/api/workspaces/{$workspace->id}", 'GET');

        $request->setRouteResolver(function () use ($workspace, $request) {
            $route = new \Illuminate\Routing\Route('GET', '/api/workspaces/{workspace}', []);
            $route->bind($request);
            $route->setParameter('workspace', $workspace);
            return $route;
        });

        $user = (object) ['id' => 1, 'email' => 'user@test.com', 'is_esporteam_admin' => false, 'workspace_id' => null];
        $request->setUserResolver(fn () => $user);

        // Act
        $result = $middleware->handle($request, fn ($r) => response()->json(['ok' => true], 200));

        // Assert
        expect($result->getStatusCode())->toBe(200);
    });
});
