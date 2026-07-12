<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleBioSuggestion
{
    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user()?->id;
        abort_unless($userId, 401, 'Unauthenticated.');

        $maxAttempts = (int) config('bio_assisted.rate_limit.max_attempts', 5);
        $decaySeconds = (int) config('bio_assisted.rate_limit.decay_seconds', 3600);
        $key = "bio-suggestion:user:{$userId}";

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => 'Muitas sugestões em pouco tempo. Aguarde e tente novamente.',
            ], 429);
        }

        $this->limiter->hit($key, $decaySeconds);

        return $next($request);
    }
}
