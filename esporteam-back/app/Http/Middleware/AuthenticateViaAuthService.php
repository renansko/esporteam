<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateViaAuthService
{
    private ?string $publicKey = null;

    public function handle(Request $request, Closure $next): Response
    {
        if (defined('APP_RUNNING_TESTS') && APP_RUNNING_TESTS) {
            return $this->mockAuthUser($request, $next);
        }

        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['success' => false, 'message' => __('messages.auth.unauthenticated')], 401);
        }

        try {
            $payload = JWT::decode($token, new Key($this->getPublicKey(), 'RS256'));
        } catch (ExpiredException) {
            return response()->json(['success' => false, 'message' => __('messages.auth.token_expired')], 401);
        } catch (\Throwable) {
            return response()->json(['success' => false, 'message' => __('messages.auth.invalid_token')], 401);
        }

        $user = (object) array_merge((array) $payload->user, [
            'is_esporteam_admin' => $payload->is_esporteam_admin ?? false,
            'is_esporteam_owner' => $payload->is_esporteam_owner ?? false,
            'profile' => $payload->profile ?? ($payload->user->profile ?? 'user'),
            'permissions' => $payload->permissions ?? 0,
        ]);
        if (!empty($payload->workspace_id)) {
            $user = (object) array_merge((array) $user, [
                'workspace_id' => $payload->workspace_id,
                'is_workspace_owner' => $payload->is_workspace_owner ?? false,
            ]);
        }

        $request->attributes->set('auth_user', (array) $payload->user);
        $request->attributes->set('profile', $payload->profile ?? ($payload->user->profile ?? 'user'));
        $request->attributes->set('permissions', $payload->permissions ?? 0);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function mockAuthUser(Request $request, Closure $next): Response
    {
        $permissions = (int) $request->header('X-Test-Permissions', '1');
        $workspaceId = (int) $request->header('X-Test-Workspace', '1');
        $userId      = (int) $request->header('X-Test-User-Id', '1');

        $user = (object) [
            'id'           => $userId,
            'email'        => 'test@example.com',
            'profile'      => $request->header('X-Test-Profile', 'user'),
            'permissions'  => $permissions,
            'workspace_id' => $workspaceId,
            'is_esporteam_admin' => ($permissions & 2) === 2,
            'is_esporteam_owner' => ($permissions & 4) === 4,
            'is_workspace_owner' => filter_var($request->header('X-Test-Workspace-Owner', false), FILTER_VALIDATE_BOOLEAN),
        ];

        $request->attributes->set('auth_user', (array) $user);
        $request->attributes->set('profile', $user->profile);
        $request->attributes->set('permissions', $permissions);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function getPublicKey(): string
    {
        if ($this->publicKey === null) {
            $this->publicKey = file_get_contents(config('jwt.public_key'));
        }

        return $this->publicKey;
    }
}
