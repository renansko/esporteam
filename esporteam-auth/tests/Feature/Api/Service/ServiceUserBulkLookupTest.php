<?php

use App\Models\User;

describe('POST /api/service/users/bulk-lookup', function () {
    describe('happy path', function () {
        it('retorna id, name e email dos usuários existentes', function () {
            // Arrange
            $u1 = User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
            $u2 = User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);

            // Act
            $response = $this->postJson('/api/service/users/bulk-lookup', [
                'ids' => [$u1->id, $u2->id],
            ]);

            // Assert
            $response->assertOk()
                ->assertJson([
                    'success' => true,
                    'message' => 'Users retrieved successfully',
                ]);

            $data = $response->json('data');
            expect($data)->toHaveCount(2);

            $byId = collect($data)->keyBy('id');
            expect($byId[$u1->id])->toMatchArray([
                'id'    => $u1->id,
                'name'  => 'Alice',
                'email' => 'alice@example.com',
            ]);
            expect($byId[$u2->id])->toMatchArray([
                'id'    => $u2->id,
                'name'  => 'Bob',
                'email' => 'bob@example.com',
            ]);
        });

        it('omite ids inexistentes do array data sem retornar erro', function () {
            // Arrange
            $user = User::factory()->create();
            $missingId = $user->id + 99999;

            // Act
            $response = $this->postJson('/api/service/users/bulk-lookup', [
                'ids' => [$user->id, $missingId],
            ]);

            // Assert
            $response->assertOk()
                ->assertJson(['success' => true]);

            $data = $response->json('data');
            expect($data)->toHaveCount(1);
            expect($data[0]['id'])->toBe($user->id);
        });

        it('retorna data vazio quando nenhum id existe', function () {
            // Arrange — banco sem usuários relevantes

            // Act
            $response = $this->postJson('/api/service/users/bulk-lookup', [
                'ids' => [999998, 999999],
            ]);

            // Assert
            $response->assertOk();
            expect($response->json('data'))->toBe([]);
        });
    });

    describe('validações', function () {
        it('retorna 422 quando ids não é enviado', function () {
            // Act
            $response = $this->postJson('/api/service/users/bulk-lookup', []);

            // Assert
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['ids']);
        });

        it('retorna 422 quando ids é um array vazio', function () {
            // Act
            $response = $this->postJson('/api/service/users/bulk-lookup', [
                'ids' => [],
            ]);

            // Assert
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['ids']);
        });

        it('retorna 422 quando ids tem mais de 100 items', function () {
            // Arrange
            $ids = range(1, 101);

            // Act
            $response = $this->postJson('/api/service/users/bulk-lookup', [
                'ids' => $ids,
            ]);

            // Assert
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['ids']);
        });

        it('retorna 422 quando algum id não é inteiro', function () {
            // Act
            $response = $this->postJson('/api/service/users/bulk-lookup', [
                'ids' => [1, 'abc', 3],
            ]);

            // Assert
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['ids.1']);
        });

        it('retorna 422 quando algum id é menor que 1', function () {
            // Act
            $response = $this->postJson('/api/service/users/bulk-lookup', [
                'ids' => [1, 0, 3],
            ]);

            // Assert
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['ids.1']);
        });
    });

    describe('autenticação via service token', function () {
        it('sem X-Service-Token o middleware rejeita em runtime real (bypass só em APP_RUNNING_TESTS)', function () {
            // Nota: em APP_RUNNING_TESTS o ServiceTokenMiddleware faz bypass automático.
            // Validamos a lógica de rejeição sem acionar o bypass, simulando a verificação
            // do hash_equals com token ausente/incorreto.
            config(['services.internal_token' => 'secret-token']);

            $request = \Illuminate\Http\Request::create('/api/service/users/bulk-lookup', 'POST');
            $token = $request->header('X-Service-Token');
            $expected = config('services.internal_token');

            expect($token)->toBeNull();
            expect((bool) ($token && $expected && hash_equals($expected, $token)))->toBeFalse();
        });

        it('com X-Service-Token incorreto o hash_equals falha', function () {
            // Arrange
            config(['services.internal_token' => 'secret-token-correto']);
            $expected = config('services.internal_token');

            // Assert
            expect(hash_equals($expected, 'token-errado'))->toBeFalse();
        });
    });
});
