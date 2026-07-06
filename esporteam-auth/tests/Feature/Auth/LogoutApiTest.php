<?php

use App\Models\User;

describe('POST /api/auth/logout', function () {
    it('marca tokens_revoked_at = now() no usuário autenticado', function () {
        $user = User::factory()->create(['permissions' => 0, 'tokens_revoked_at' => null]);

        $response = $this->postJson('/api/auth/logout');

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => 'Sessão encerrada.',
        ]);

        $user->refresh();
        expect($user->tokens_revoked_at)->not->toBeNull()
            ->and($user->tokens_revoked_at->diffInSeconds(now()))->toBeLessThan(5);
    });

    it('é idempotente — chamar duas vezes só atualiza o timestamp', function () {
        User::factory()->create(['permissions' => 0, 'tokens_revoked_at' => null]);

        $this->postJson('/api/auth/logout')->assertOk();
        $first = User::first()->tokens_revoked_at;

        sleep(1);
        $this->postJson('/api/auth/logout')->assertOk();
        $second = User::first()->tokens_revoked_at;

        expect($second->gt($first))->toBeTrue();
    });
});
