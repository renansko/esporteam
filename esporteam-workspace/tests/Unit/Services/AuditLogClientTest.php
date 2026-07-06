<?php

use App\Services\AuditLogClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

describe('AuditLogClient', function () {
    beforeEach(function () {
        config([
            'services.auth.url'           => 'http://esporteam-auth',
            'services.auth.service_token' => 'secret-service-token',
        ]);
    });

    describe('log', function () {
        it('envia request HTTP para esporteam-auth com X-Service-Token e body correto', function () {
            // Arrange
            Http::fake(['http://esporteam-auth/*' => Http::response(['success' => true], 200)]);
            $client = new AuditLogClient();

            // Act
            $client->log(
                adminId: 1,
                adminEmail: 'admin@esporteam.com',
                action: 'deactivate_workspace',
                targetType: 'workspace',
                targetId: 42,
                snapshot: ['name' => 'Escola Teste', 'slug' => 'escola-teste'],
                metadata: [],
            );

            // Assert
            Http::assertSent(function ($request) {
                return $request->url() === 'http://esporteam-auth/api/service/audit'
                    && $request->hasHeader('X-Service-Token', 'secret-service-token')
                    && $request->data()['admin_user_id'] === 1
                    && $request->data()['admin_email'] === 'admin@esporteam.com'
                    && $request->data()['action'] === 'deactivate_workspace'
                    && $request->data()['target_type'] === 'workspace'
                    && $request->data()['target_id'] === 42;
            });
        });

        it('NÃO lança exceção quando a chamada HTTP falha com erro de conexão', function () {
            // Arrange
            Http::fake(['*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('Connection refused')]);
            Log::spy();
            $client = new AuditLogClient();

            // Act — NÃO deve lançar exceção
            expect(fn () => $client->log(
                adminId: 1,
                adminEmail: 'admin@esporteam.com',
                action: 'activate_workspace',
                targetType: 'workspace',
                targetId: 10,
            ))->not->toThrow(\Throwable::class);

            // Assert — Log::error foi chamado
            Log::shouldHaveReceived('error')->once();
        });

        it('NÃO lança exceção quando a resposta HTTP retorna status de erro (5xx)', function () {
            // Arrange
            Http::fake(['*' => Http::response(['success' => false], 500)]);
            Log::spy();
            $client = new AuditLogClient();

            // Act — NÃO deve lançar exceção
            expect(fn () => $client->log(
                adminId: 2,
                adminEmail: 'admin2@esporteam.com',
                action: 'deactivate_workspace',
                targetType: 'workspace',
                targetId: 5,
            ))->not->toThrow(\Throwable::class);

            // Assert — Log::error foi chamado para sinalizar resposta não-sucesso
            Log::shouldHaveReceived('error')->once();
        });

        it('envia snapshot e metadata no body da request', function () {
            // Arrange
            Http::fake(['*' => Http::response(['success' => true], 200)]);
            $client = new AuditLogClient();
            $snapshot = ['name' => 'Escola X', 'slug' => 'escola-x'];
            $metadata = ['extra' => 'info'];

            // Act
            $client->log(
                adminId: 1,
                adminEmail: 'admin@esporteam.com',
                action: 'test_action',
                targetType: 'workspace',
                targetId: 99,
                snapshot: $snapshot,
                metadata: $metadata,
            );

            // Assert
            Http::assertSent(function ($request) use ($snapshot, $metadata) {
                return $request->data()['snapshot'] === $snapshot
                    && $request->data()['metadata'] === $metadata;
            });
        });
    });
});
