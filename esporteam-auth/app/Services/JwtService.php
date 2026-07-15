<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private string $privateKey;
    private string $publicKey;

    public function __construct()
    {
        $this->privateKey = file_get_contents(config('jwt.private_key'));
        $this->publicKey = file_get_contents(config('jwt.public_key'));
    }

    public function encode(array $user, ?string $workspaceId = null, ?array $schoolPermissions = null, bool $isWorkspaceOwner = false): string
    {
        $now = time();

        $payload = [
            'iss' => config('app.url'),
            'sub' => $user['id'],
            'iat' => $now,
            'exp' => $now + config('jwt.ttl'),
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'profile' => $user['profile'] ?? 'user',
            ],
            'profile' => $user['profile'] ?? 'user',
            'permissions' => $user['permissions'] ?? 0,
            'is_esporteam_admin' => (($user['permissions'] ?? 0) & 2) === 2,
            'is_esporteam_owner' => (($user['permissions'] ?? 0) & 4) === 4,
            'is_adult' => (bool) ($user['is_adult'] ?? false),
        ];

        if ($workspaceId) {
            $payload['workspace_id'] = $workspaceId;
            $payload['is_workspace_owner'] = $isWorkspaceOwner;
        }

        if ($schoolPermissions !== null) {
            $payload['school_permissions'] = $schoolPermissions;
        }

        return JWT::encode($payload, $this->privateKey, 'RS256');
    }

    public function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->publicKey, 'RS256'));
    }

    /**
     * Encodes an impersonation JWT. The target user does NOT inherit
     * esporteam admin privileges; the token is short-lived (1h hard-coded).
     */
    public function encodeImpersonation(
        array $targetUser,
        int $adminId,
        ?int $workspaceId = null,
        ?bool $isWorkspaceOwner = null,
        ?array $schoolPermissions = null
    ): string {
        $now = time();

        $payload = [
            'iss'             => config('app.url'),
            'sub'             => $targetUser['id'],
            'iat'             => $now,
            'exp'             => $now + 3600,
            'user'            => [
                'id'    => $targetUser['id'],
                'name'  => $targetUser['name'],
                'email' => $targetUser['email'],
                'profile' => $targetUser['profile'] ?? 'user',
            ],
            'profile'     => $targetUser['profile'] ?? 'user',
            'permissions'     => $targetUser['permissions'] ?? 0,
            'is_esporteam_admin'   => false,
            'is_esporteam_owner'   => false,
            'is_adult' => (bool) ($targetUser['is_adult'] ?? false),
            'impersonated_by' => $adminId,
            'impersonated_at' => $now,
        ];

        if ($workspaceId !== null) {
            $payload['workspace_id']       = $workspaceId;
            $payload['is_workspace_owner'] = (bool) $isWorkspaceOwner;

            if ($schoolPermissions !== null) {
                $payload['school_permissions'] = $schoolPermissions;
            }
        }

        return JWT::encode($payload, $this->privateKey, 'RS256');
    }
}
