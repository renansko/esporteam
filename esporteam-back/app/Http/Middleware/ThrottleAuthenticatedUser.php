<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleAuthenticatedUser
{
    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next, string $surface): Response
    {
        $userId = $request->user()?->id;
        abort_unless($userId, 401, 'Unauthenticated.');

        $maxAttempts = (int) config("discovery.rate_limits.{$surface}_per_minute");
        $key = "{$surface}:user:{$userId}";

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => 'Muitas atualizacoes em pouco tempo. Aguarde um minuto e tente novamente.',
            ], 429);
        }

        $this->limiter->hit($key, 60);

        return $next($request);
    }
}
