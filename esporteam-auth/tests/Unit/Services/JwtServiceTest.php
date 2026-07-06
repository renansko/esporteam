<?php

use App\Services\JwtService;

describe('JwtService', function () {
    beforeEach(function () {
        $keyPair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($keyPair, $privateKey);
        $publicKey = openssl_pkey_get_details($keyPair)['key'];

        $keysDir = sys_get_temp_dir() . '/esporteam-auth-test-keys';
        if (!is_dir($keysDir)) {
            mkdir($keysDir, 0700, true);
        }

        file_put_contents($keysDir . '/private.pem', $privateKey);
        file_put_contents($keysDir . '/public.pem', $publicKey);

        config([
            'jwt.private_key' => $keysDir . '/private.pem',
            'jwt.public_key' => $keysDir . '/public.pem',
            'jwt.ttl' => 3600,
        ]);
    });

    it('should include permissions in jwt payload', function () {
        $jwtService = new JwtService();
        $userData = [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'profile' => 'teacher',
            'permissions' => 5,
        ];

        $token = $jwtService->encode($userData);
        $decoded = $jwtService->decode($token);

        expect($decoded->permissions)->toBe(5);
        expect($decoded->profile)->toBe('teacher');
        expect($decoded->user->profile)->toBe('teacher');
        expect(isset($decoded->user->role))->toBeFalse();
    });

    it('should include school_permissions when provided', function () {
        $jwtService = new JwtService();
        $userData = [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'permissions' => 1,
        ];

        $schoolPerms = ['students' => 15, 'diary' => 2];
        $token = $jwtService->encode($userData, '1', $schoolPerms);
        $decoded = $jwtService->decode($token);

        expect($decoded->permissions)->toBe(1);
        expect($decoded->workspace_id)->toBe('1');
        expect((array) $decoded->school_permissions)->toBe($schoolPerms);
    });

    it('should not include school_permissions when null', function () {
        $jwtService = new JwtService();
        $userData = [
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'permissions' => 0,
        ];

        $token = $jwtService->encode($userData);
        $decoded = $jwtService->decode($token);

        expect(isset($decoded->school_permissions))->toBeFalse();
    });

    describe('encodeImpersonation', function () {
        $targetUser = fn () => [
            'id'          => 10,
            'name'        => 'Usuário Alvo',
            'email'       => 'alvo@escola.com',
            'permissions' => 0,
        ];

        it('produz payload com is_esporteam_admin sempre false', function () use ($targetUser) {
            // Arrange
            $jwtService = new JwtService();

            // Act
            $token = $jwtService->encodeImpersonation($targetUser(), adminId: 1);
            $decoded = $jwtService->decode($token);

            // Assert
            expect($decoded->is_esporteam_admin)->toBeFalse();
        });

        it('TTL é exatamente 3600 segundos independente de config(jwt.ttl)', function () use ($targetUser) {
            // Arrange — define TTL diferente para garantir que não é herdado
            config(['jwt.ttl' => 86400]);
            $jwtService = new JwtService();

            // Act
            $token = $jwtService->encodeImpersonation($targetUser(), adminId: 1);
            $decoded = $jwtService->decode($token);

            // Assert
            expect($decoded->exp - $decoded->iat)->toBe(3600);
        });

        it('contém impersonated_by com o id do admin e impersonated_at', function () use ($targetUser) {
            // Arrange
            $jwtService = new JwtService();

            // Act
            $token = $jwtService->encodeImpersonation($targetUser(), adminId: 7);
            $decoded = $jwtService->decode($token);

            // Assert
            expect($decoded->impersonated_by)->toBe(7);
            expect(isset($decoded->impersonated_at))->toBeTrue();
        });

        it('com workspace_id inclui workspace_id, is_workspace_owner e school_permissions', function () use ($targetUser) {
            // Arrange
            $jwtService = new JwtService();
            $schoolPerms = ['students' => 15, 'diary' => 3];

            // Act
            $token = $jwtService->encodeImpersonation(
                $targetUser(),
                adminId: 1,
                workspaceId: 99,
                isWorkspaceOwner: false,
                schoolPermissions: $schoolPerms,
            );
            $decoded = $jwtService->decode($token);

            // Assert
            expect($decoded->workspace_id)->toBe(99);
            expect($decoded->is_workspace_owner)->toBeFalse();
            expect((array) $decoded->school_permissions)->toBe($schoolPerms);
        });

        it('sem workspace_id NÃO inclui workspace_id nem is_workspace_owner nem school_permissions', function () use ($targetUser) {
            // Arrange
            $jwtService = new JwtService();

            // Act
            $token = $jwtService->encodeImpersonation($targetUser(), adminId: 1);
            $decoded = $jwtService->decode($token);

            // Assert
            expect(isset($decoded->workspace_id))->toBeFalse();
            expect(isset($decoded->is_workspace_owner))->toBeFalse();
            expect(isset($decoded->school_permissions))->toBeFalse();
        });
    });
});
