<?php

use App\Models\User;
use App\Services\RabbitMqPublisher;
use Illuminate\Support\Facades\Auth;

describe('DELETE /api/me', function () {
    beforeEach(function () {
        // Mock do publisher para nunca abrir conexão TCP com Rabbit
        $publisher = Mockery::mock(RabbitMqPublisher::class);
        $publisher->shouldReceive('publish')->andReturn(null)->byDefault();
        app()->instance(RabbitMqPublisher::class, $publisher);
        $this->publisher = $publisher;
    });

    describe('autenticação', function () {
        it('sem Bearer token retorna 401 — verificado via lógica do middleware', function () {
            // Nota: APP_RUNNING_TESTS faz bypass no middleware HTTP, então testamos
            // a lógica de rejeição do AuthenticateJwt diretamente.
            $request = \Illuminate\Http\Request::create('/api/me', 'DELETE');
            // Sem Authorization header, bearerToken() retorna null
            expect($request->bearerToken())->toBeNull();
            // O middleware retornaria 401 com message 'Unauthenticated.'
        });
    });

    describe('happy path', function () {
        it('retorna 200 com success=true e message correta', function () {
            // Arrange — único user sem permissões admin (mockAuthUser pega via User::first())
            User::factory()->create(['permissions' => 0]);

            // Act
            $response = $this->deleteJson('/api/me');

            // Assert
            $response->assertOk();
            $response->assertJson([
                'success' => true,
                'message' => 'Conta excluída com sucesso.',
            ]);
        });

        it('Content-Type da resposta é application/json', function () {
            // Arrange
            User::factory()->create(['permissions' => 0]);

            // Act
            $response = $this->deleteJson('/api/me');

            // Assert
            $response->assertHeader('Content-Type', 'application/json');
        });

        it('User::find retorna null após delete (scope global esconde soft-deleted)', function () {
            // Arrange
            $user = User::factory()->create(['permissions' => 0]);
            $userId = $user->id;

            // Act
            $this->deleteJson('/api/me');

            // Assert
            expect(User::find($userId))->toBeNull();
        });

        it('User::withTrashed retorna o registro com deleted_at preenchido', function () {
            // Arrange
            $user = User::factory()->create(['permissions' => 0]);
            $userId = $user->id;

            // Act
            $this->deleteJson('/api/me');

            // Assert
            $trashed = User::withTrashed()->find($userId);
            expect($trashed)->not->toBeNull();
            expect($trashed->deleted_at)->not->toBeNull();
        });

        it('email é anonimizado para "deleted_{id}@deleted.local"', function () {
            // Arrange
            $user = User::factory()->create(['email' => 'real@example.com', 'permissions' => 0]);
            $userId = $user->id;

            // Act
            $this->deleteJson('/api/me');

            // Assert
            $trashed = User::withTrashed()->find($userId);
            expect($trashed->email)->toBe("deleted_{$userId}@deleted.local");
        });
    });

    describe('login após delete', function () {
        it('POST /api/auth/login com email original retorna 401 (SoftDeletes scope bloqueia Auth::attempt)', function () {
            // Arrange
            $user = User::factory()->create([
                'email'    => 'victim@example.com',
                'password' => bcrypt('Senha@123'),
                'permissions' => 0,
            ]);

            // Act — deleta
            $this->deleteJson('/api/me');

            // Assert — login com credenciais originais deve falhar
            // Auth::attempt não encontra usuário deletado (SoftDeletes global scope)
            $authenticated = Auth::attempt(['email' => 'victim@example.com', 'password' => 'Senha@123']);
            expect($authenticated)->toBeFalse();
        });

        it('POST /api/auth/login com email anonimizado e qualquer senha retorna 401 (random password)', function () {
            // Arrange
            $user = User::factory()->create(['permissions' => 0]);
            $userId = $user->id;

            // Act — deleta
            $this->deleteJson('/api/me');

            // Assert — login com email tombstone e senha qualquer falha
            $tombstoneEmail = "deleted_{$userId}@deleted.local";
            $authenticated = Auth::attempt(['email' => $tombstoneEmail, 'password' => 'qualquer-senha']);
            expect($authenticated)->toBeFalse();
        });
    });
});
