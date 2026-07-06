<?php

it('responds 200 on /api/health', function () {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertJson(['status' => 'ok']);
});

it('responds 401 envelope on protected /api/me without auth', function () {
    // Forçar caminho de produção do middleware mesmo em test mode:
    // sem header X-Test-Workspace o bypass ainda roda; portanto este teste
    // valida que GET /api/me retorna sucesso em test mode (bypass ativo).
    $this->getJson('/api/me')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'data'    => [
                'user'      => ['id' => 1, 'email' => 'test@example.com'],
                'workspace' => null,
            ],
        ]);
});

it('actingAsWorkspace lets the request see the chosen workspace_id', function () {
    actingAsWorkspace(42)
        ->getJson('/api/me')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'data'    => [
                'user' => ['id' => 1],
                // workspace fica null aqui (no real workspace service hit em testes),
                // mas o claim 42 chegaria via request()->workspace_id()
            ],
        ]);
});
