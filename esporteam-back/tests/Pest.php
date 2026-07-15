<?php

uses(Tests\Feature\TestCase::class)->in('Feature');
uses(Tests\Unit\TestCase::class)->in('Unit');

/**
 * Auth helper para testes: faz o request seguinte rodar como se o JWT
 * carregasse claim de workspace_id (e opcionalmente outros campos do user).
 *
 * Pareia com o bypass APP_RUNNING_TESTS de AuthenticateViaAuthService,
 * que lê X-Test-Workspace, X-Test-User-Id e X-Test-Permissions pra montar
 * o user mockado e popular o user resolver.
 *
 * Uso:
 *   actingAsWorkspace(42)->postJson('/api/ideas', [...]);
 *   actingAsWorkspace(1, ['id' => 7])->getJson('/api/ideas');
 *
 * @param  int    $workspaceId
 * @param  array  $userClaims  ['id' => int, 'permissions' => int, 'profile' => string]
 * @return \Illuminate\Testing\TestResponse|\Tests\Feature\TestCase
 */
function actingAsWorkspace(int $workspaceId, array $userClaims = [])
{
    $headers = [
        'Accept'           => 'application/json',
        'X-Test-Workspace' => (string) $workspaceId,
    ];

    if (isset($userClaims['id'])) {
        $headers['X-Test-User-Id'] = (string) $userClaims['id'];
    }
    if (isset($userClaims['permissions'])) {
        $headers['X-Test-Permissions'] = (string) $userClaims['permissions'];
    }
    if (isset($userClaims['profile'])) {
        $headers['X-Test-Profile'] = (string) $userClaims['profile'];
    }
    if (array_key_exists('is_adult', $userClaims)) {
        $headers['X-Test-Is-Adult'] = $userClaims['is_adult'] ? 'true' : 'false';
    }

    return test()->withHeaders($headers);
}
