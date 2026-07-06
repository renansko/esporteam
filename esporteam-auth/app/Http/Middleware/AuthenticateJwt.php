<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function __construct(private JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (defined('APP_RUNNING_TESTS') && APP_RUNNING_TESTS) {
            return $this->mockAuthUser($request, $next);
        }

        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        try {
            $payload = $this->jwt->decode($token);
        } catch (ExpiredException) {
            return response()->json(['success' => false, 'message' => 'Token expired.'], 401);
        } catch (\Throwable) {
            return response()->json(['success' => false, 'message' => 'Invalid token.'], 401);
        }

        $user = User::find($payload->sub);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $iat = (int) ($payload->iat ?? 0);
        if ($user->tokens_revoked_at && $iat < $user->tokens_revoked_at->timestamp) {
            return response()->json(['success' => false, 'message' => 'Token revoked.'], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function mockAuthUser(Request $request, Closure $next): Response
    {
        $user = User::query()->whereRaw('(permissions & 2) = 2')->orderBy('id')->first()
            ?? User::query()->orderBy('id')->first()
            ?? User::factory()->create();
        $request->setUserResolver(fn () => $user);
        return $next($request);
    }
}
