<?php

use App\Models\User;
use App\Services\RabbitMqPublisher;
use App\Services\UserService;
use Illuminate\Support\Facades\Log;

describe('UserService::softDelete', function () {
    describe('anonimização', function () {
        it('deve anonimizar name para "Usuário removido"', function () {
            // Arrange
            $user = User::factory()->create(['name' => 'João Silva']);
            $publisher = Mockery::mock(RabbitMqPublisher::class);
            $publisher->shouldReceive('publish')->once();
            $service = new UserService($publisher);

            // Act
            $service->softDelete($user);

            // Assert
            expect(User::withTrashed()->find($user->id)->name)->toBe('Usuário removido');
        });

        it('deve anonimizar email para "deleted_{id}@deleted.local"', function () {
            // Arrange
            $user = User::factory()->create(['email' => 'real@example.com']);
            $publisher = Mockery::mock(RabbitMqPublisher::class);
            $publisher->shouldReceive('publish')->once();
            $service = new UserService($publisher);

            // Act
            $service->softDelete($user);

            // Assert
            $tombstone = User::withTrashed()->find($user->id);
            expect($tombstone->email)->toBe("deleted_{$user->id}@deleted.local");
        });

        it('deve zerar two_factor_secret, two_factor_recovery_codes e two_factor_confirmed_at', function () {
            // Arrange
            $user = User::factory()->create([
                'two_factor_secret'         => 'SOMESECRET',
                'two_factor_recovery_codes' => json_encode(['code1', 'code2']),
                'two_factor_confirmed_at'   => now(),
            ]);
            $publisher = Mockery::mock(RabbitMqPublisher::class);
            $publisher->shouldReceive('publish')->once();
            $service = new UserService($publisher);

            // Act
            $service->softDelete($user);

            // Assert
            $fresh = User::withTrashed()->find($user->id);
            expect($fresh->two_factor_secret)->toBeNull();
            expect($fresh->two_factor_recovery_codes)->toBeNull();
            expect($fresh->two_factor_confirmed_at)->toBeNull();
        });

        it('deve alterar a senha (não fica igual à original)', function () {
            // Arrange
            $originalHash = bcrypt('SenhaOriginal123');
            $user = User::factory()->create(['password' => $originalHash]);
            $publisher = Mockery::mock(RabbitMqPublisher::class);
            $publisher->shouldReceive('publish')->once();
            $service = new UserService($publisher);

            // Act
            $service->softDelete($user);

            // Assert
            $fresh = User::withTrashed()->find($user->id);
            expect($fresh->password)->not->toBe($originalHash);
            expect($fresh->password)->not->toBeEmpty();
        });
    });

    describe('soft delete', function () {
        it('deve setar deleted_at (user invisível no scope padrão)', function () {
            // Arrange
            $user = User::factory()->create();
            $userId = $user->id;
            $publisher = Mockery::mock(RabbitMqPublisher::class);
            $publisher->shouldReceive('publish')->once();
            $service = new UserService($publisher);

            // Act
            $service->softDelete($user);

            // Assert — invisível no scope padrão
            expect(User::find($userId))->toBeNull();

            // Assert — visível via withTrashed com deleted_at preenchido
            $trashed = User::withTrashed()->find($userId);
            expect($trashed)->not->toBeNull();
            expect($trashed->deleted_at)->not->toBeNull();
        });
    });

    describe('publicação de evento RabbitMQ', function () {
        it('deve chamar publish com exchange "esporteam.events", routing key "user.deleted" e payload correto', function () {
            // Arrange
            $user = User::factory()->create();
            $capturedPayload = null;

            $publisher = Mockery::mock(RabbitMqPublisher::class);
            $publisher->shouldReceive('publish')
                ->once()
                ->withArgs(function (string $exchange, string $routingKey, array $payload) use (&$capturedPayload) {
                    $capturedPayload = $payload;
                    return $exchange === 'esporteam.events' && $routingKey === 'user.deleted';
                });

            app()->instance(RabbitMqPublisher::class, $publisher);
            $service = new UserService($publisher);

            // Act
            $service->softDelete($user);

            // Assert
            expect($capturedPayload)->toMatchArray([
                'event_name' => 'user.deleted',
                'data'       => ['user_id' => $user->id],
            ]);
            expect($capturedPayload['event_id'])->toMatch(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
            );
            expect($capturedPayload['occurred_at'])->not->toBeEmpty();
        });

        it('NÃO deve propagar exceção quando o publisher lança erro', function () {
            // Arrange
            $user = User::factory()->create();
            $publisher = Mockery::mock(RabbitMqPublisher::class);
            $publisher->shouldReceive('publish')->once()->andThrow(new \RuntimeException('Rabbit offline'));
            Log::shouldReceive('error')->once();
            $service = new UserService($publisher);

            // Act & Assert — não deve lançar
            expect(fn () => $service->softDelete($user))->not->toThrow(\Throwable::class);

            // O soft delete deve ter ocorrido mesmo assim
            expect(User::find($user->id))->toBeNull();
            expect(User::withTrashed()->find($user->id))->not->toBeNull();
        });
    });

    describe('idempotência', function () {
        it('chamar softDelete 2x no mesmo usuário não causa exceção', function () {
            // Arrange
            $user = User::factory()->create();
            $publisher = Mockery::mock(RabbitMqPublisher::class);
            $publisher->shouldReceive('publish')->twice();
            $service = new UserService($publisher);

            // Act — primeira chamada
            $service->softDelete($user);

            // Recarregar com withTrashed para obter o model soft-deleted
            $trashedUser = User::withTrashed()->find($user->id);

            // Act — segunda chamada (não deve lançar)
            expect(fn () => $service->softDelete($trashedUser))->not->toThrow(\Throwable::class);
        });
    });

    describe('owner da plataforma', function () {
        it('deve deletar normalmente um usuário com permissão de owner (permissions & 4)', function () {
            // Arrange — owner tem bit 2 (valor 4)
            $owner = User::factory()->create(['permissions' => 7]);
            $publisher = Mockery::mock(RabbitMqPublisher::class);
            $publisher->shouldReceive('publish')->once();
            $service = new UserService($publisher);

            // Act
            $service->softDelete($owner);

            // Assert
            expect(User::find($owner->id))->toBeNull();
            expect(User::withTrashed()->find($owner->id)->deleted_at)->not->toBeNull();
        });
    });
});
