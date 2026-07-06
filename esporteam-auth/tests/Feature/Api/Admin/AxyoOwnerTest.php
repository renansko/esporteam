<?php

use App\Models\User;
use App\Services\JwtService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

describe('Esporteam Owner role (bit 2, value 4)', function () {

    describe('PUT /api/admin/users/{id}/permissions middleware', function () {
        it('admin comum (bit 1 only) recebe 403 — não é owner', function () {
            // Arrange: mockAuthUser usa User::first(), então o admin é o primeiro.
            User::query()->delete();
            User::factory()->create(['permissions' => 2]); // admin, NOT owner
            $target = User::factory()->create(['permissions' => 0]);

            // Act
            $response = $this->putJson("/api/admin/users/{$target->id}/permissions", [
                'permissions' => 1,
            ]);

            // Assert
            $response->assertForbidden()
                ->assertJson(['success' => false]);
        });

        it('owner (bit 2) consegue editar permissões de outro user', function () {
            // Arrange
            User::query()->delete();
            User::factory()->create(['permissions' => 6]); // owner + admin
            $target = User::factory()->create(['permissions' => 0]);

            // Act
            $response = $this->putJson("/api/admin/users/{$target->id}/permissions", [
                'permissions' => 3,
            ]);

            // Assert
            $response->assertOk()->assertJson(['success' => true]);
            expect($target->fresh()->permissions)->toBe(3);
        });
    });

    describe('Self-service PUT /api/users/{id}/permissions anti-escalation', function () {
        it('strippa bit 2 (owner) do input — usuário comum não vira owner', function () {
            // Arrange
            User::query()->delete();
            $user = User::factory()->create(['permissions' => 0]);

            // Act — tenta setar bit 2 (owner)
            $response = $this->putJson("/api/users/{$user->id}/permissions", [
                'permissions' => 4,
            ]);

            // Assert
            $response->assertOk();
            // bit 2 foi strippado; 4 & ~6 = 0
            expect($user->fresh()->permissions)->toBe(0);
        });

        it('strippa bit 1 (admin) do input — usuário comum não vira admin', function () {
            // Arrange
            User::query()->delete();
            $user = User::factory()->create(['permissions' => 0]);

            // Act
            $response = $this->putJson("/api/users/{$user->id}/permissions", [
                'permissions' => 2,
            ]);

            // Assert
            $response->assertOk();
            expect($user->fresh()->permissions)->toBe(0);
        });

        it('preserva bits 1 e 2 existentes na DB — owner não perde o bit via self-service', function () {
            // Arrange
            User::query()->delete();
            $user = User::factory()->create(['permissions' => 6]); // owner + admin

            // Act — tenta zerar tudo
            $response = $this->putJson("/api/users/{$user->id}/permissions", [
                'permissions' => 0,
            ]);

            // Assert
            $response->assertOk();
            // bits 1 e 2 preservados da DB
            expect($user->fresh()->permissions)->toBe(6);
        });

        it('permite setar bit 0 (can_create_workspace) mantendo owner bit', function () {
            // Arrange
            User::query()->delete();
            $user = User::factory()->create(['permissions' => 6]);

            // Act
            $response = $this->putJson("/api/users/{$user->id}/permissions", [
                'permissions' => 1,
            ]);

            // Assert
            $response->assertOk();
            expect($user->fresh()->permissions)->toBe(7); // 1 | 6
        });
    });

    describe('Impersonation bloqueia owners', function () {
        beforeEach(function () {
            $keyPair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($keyPair, $privateKey);
            $publicKey = openssl_pkey_get_details($keyPair)['key'];

            $keysDir = sys_get_temp_dir() . '/esporteam-auth-owner-test-keys';
            if (!is_dir($keysDir)) {
                mkdir($keysDir, 0700, true);
            }
            file_put_contents($keysDir . '/private.pem', $privateKey);
            file_put_contents($keysDir . '/public.pem', $publicKey);

            config([
                'jwt.private_key' => $keysDir . '/private.pem',
                'jwt.public_key'  => $keysDir . '/public.pem',
            ]);
        });

        it('admin NÃO consegue impersonar um owner — retorna 422', function () {
            // Arrange
            User::query()->delete();
            User::factory()->create(['permissions' => 2]); // admin (auth mock)
            $owner = User::factory()->create(['permissions' => 4]); // owner-only

            // Act
            $response = $this->postJson('/api/admin/impersonate', [
                'user_id' => $owner->id,
            ]);

            // Assert
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['user_id']);
        });
    });

    describe('JWT claim is_esporteam_owner', function () {
        it('JwtService::encode inclui is_esporteam_owner=true quando bit 2 setado', function () {
            // Arrange
            $keyPair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($keyPair, $privateKey);
            $publicKey = openssl_pkey_get_details($keyPair)['key'];

            $keysDir = sys_get_temp_dir() . '/esporteam-auth-owner-jwt-test-keys';
            if (!is_dir($keysDir)) {
                mkdir($keysDir, 0700, true);
            }
            file_put_contents($keysDir . '/private.pem', $privateKey);
            file_put_contents($keysDir . '/public.pem', $publicKey);

            config([
                'jwt.private_key' => $keysDir . '/private.pem',
                'jwt.public_key'  => $keysDir . '/public.pem',
            ]);

            $jwt = new JwtService();

            // Act
            $token = $jwt->encode([
                'id'          => 1,
                'name'        => 'Owner',
                'email'       => 'owner@esporteam.com',
                'permissions' => 7,
            ]);

            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));

            // Assert
            expect($decoded->is_esporteam_owner)->toBeTrue();
            expect($decoded->is_esporteam_admin)->toBeTrue();
        });

        it('JwtService::encode retorna is_esporteam_owner=false para user sem bit 2', function () {
            // Arrange
            $keyPair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($keyPair, $privateKey);
            $publicKey = openssl_pkey_get_details($keyPair)['key'];

            $keysDir = sys_get_temp_dir() . '/esporteam-auth-owner-jwt-test-keys2';
            if (!is_dir($keysDir)) {
                mkdir($keysDir, 0700, true);
            }
            file_put_contents($keysDir . '/private.pem', $privateKey);
            file_put_contents($keysDir . '/public.pem', $publicKey);

            config([
                'jwt.private_key' => $keysDir . '/private.pem',
                'jwt.public_key'  => $keysDir . '/public.pem',
            ]);

            $jwt = new JwtService();

            // Act — admin only
            $token = $jwt->encode([
                'id'          => 2,
                'name'        => 'Admin',
                'email'       => 'admin@esporteam.com',
                'permissions' => 2,
            ]);

            $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));

            // Assert
            expect($decoded->is_esporteam_owner)->toBeFalse();
            expect($decoded->is_esporteam_admin)->toBeTrue();
        });
    });
});
