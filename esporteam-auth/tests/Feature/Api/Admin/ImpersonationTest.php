<?php

use App\Models\AdminAuditLog;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

describe('POST /api/admin/impersonate', function () {
    beforeEach(function () {
        // O mockAuthUser do AuthenticateJwt usa User::first().
        // Criamos o admin primeiro para que ele seja o usuário autenticado no mock.
        $this->admin = User::factory()->create(['permissions' => 2]);
        $this->target = User::factory()->create(['permissions' => 0]);
    });

    describe('happy path', function () {
        // Configura chaves RSA reais para poder decodificar o token retornado
        beforeEach(function () {
            $keyPair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($keyPair, $privateKey);
            $this->jwtPublicKey = openssl_pkey_get_details($keyPair)['key'];

            $keysDir = sys_get_temp_dir() . '/esporteam-auth-impersonation-test-keys';
            if (!is_dir($keysDir)) {
                mkdir($keysDir, 0700, true);
            }
            file_put_contents($keysDir . '/private.pem', $privateKey);
            file_put_contents($keysDir . '/public.pem', $this->jwtPublicKey);

            config([
                'jwt.private_key' => $keysDir . '/private.pem',
                'jwt.public_key'  => $keysDir . '/public.pem',
            ]);
        });

        it('admin consegue impersonar usuário comum e recebe 200 com token e expires_at', function () {
            // Act
            $response = $this->postJson('/api/admin/impersonate', [
                'user_id' => $this->target->id,
            ]);

            // Assert
            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => ['token', 'expires_at', 'user'],
                ])
                ->assertJson(['success' => true]);
        });

        it('token retornado tem TTL de 3600 segundos (exp - iat === 3600)', function () {
            // Act
            $response = $this->postJson('/api/admin/impersonate', ['user_id' => $this->target->id]);
            $response->assertOk();

            $decoded = JWT::decode($response->json('data.token'), new Key($this->jwtPublicKey, 'RS256'));

            // Assert
            expect($decoded->exp - $decoded->iat)->toBe(3600);
        });

        it('token retornado tem is_esporteam_admin === false', function () {
            // Act
            $response = $this->postJson('/api/admin/impersonate', ['user_id' => $this->target->id]);
            $response->assertOk();

            $decoded = JWT::decode($response->json('data.token'), new Key($this->jwtPublicKey, 'RS256'));

            // Assert
            expect($decoded->is_esporteam_admin)->toBeFalse();
        });

        it('token retornado tem impersonated_by igual ao id do admin', function () {
            // Act
            $response = $this->postJson('/api/admin/impersonate', ['user_id' => $this->target->id]);
            $response->assertOk();

            $decoded = JWT::decode($response->json('data.token'), new Key($this->jwtPublicKey, 'RS256'));

            // Assert
            expect($decoded->impersonated_by)->toBe($this->admin->id);
        });

        it('token retornado tem sub igual ao id do target', function () {
            // Act
            $response = $this->postJson('/api/admin/impersonate', ['user_id' => $this->target->id]);
            $response->assertOk();

            $decoded = JWT::decode($response->json('data.token'), new Key($this->jwtPublicKey, 'RS256'));

            // Assert
            expect($decoded->sub)->toBe($this->target->id);
        });

        it('audit log é gravado com action impersonate', function () {
            // Act
            $this->postJson('/api/admin/impersonate', ['user_id' => $this->target->id])
                ->assertOk();

            // Assert
            $log = AdminAuditLog::where('action', 'impersonate')
                ->where('target_id', $this->target->id)
                ->first();

            expect($log)->not->toBeNull();
            expect($log->admin_user_id)->toBe($this->admin->id);
        });
    });

    describe('edge cases e validações', function () {
        it('admin NÃO consegue impersonar outro admin — retorna 422', function () {
            // Arrange
            $outroAdmin = User::factory()->create(['permissions' => 2]);

            // Act
            $response = $this->postJson('/api/admin/impersonate', [
                'user_id' => $outroAdmin->id,
            ]);

            // Assert
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['user_id']);
        });

        it('user comum (sem flag admin) recebe 403 na rota', function () {
            // Arrange — recria DB com user sem admin como primeiro (será o mockado)
            User::query()->delete();
            User::factory()->create(['permissions' => 0]);
            $target = User::factory()->create(['permissions' => 0]);

            // Act
            $response = $this->postJson('/api/admin/impersonate', [
                'user_id' => $target->id,
            ]);

            // Assert
            $response->assertForbidden()
                ->assertJson(['success' => false]);
        });

        it('retorna 422 quando user_id não é enviado', function () {
            // Act
            $response = $this->postJson('/api/admin/impersonate', []);

            // Assert
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['user_id']);
        });

        it('retorna 404 quando user_id não existe', function () {
            // Act
            $response = $this->postJson('/api/admin/impersonate', ['user_id' => 99999]);

            // Assert
            $response->assertNotFound();
        });
    });
});
