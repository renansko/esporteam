<?php

namespace App\Http\Middleware;

use App\Models\SportProfile;
use App\Services\AiOperationalAudit;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleBioSuggestion
{
    public function __construct(
        private readonly RateLimiter $limiter,
        private readonly AiOperationalAudit $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user()?->id;
        abort_unless($userId, 401, 'Unauthenticated.');

        $maxAttempts = (int) config('bio_assisted.rate_limit.max_attempts', 5);
        $decaySeconds = (int) config('bio_assisted.rate_limit.decay_seconds', 3600);
        $key = "bio-suggestion:user:{$userId}";
        $idempotencyKey = trim((string) $request->header('Idempotency-Key'));

        if ($idempotencyKey !== '' && SportProfile::query()
            ->where('user_id', $userId)
            ->whereHas('bioSuggestions', fn ($query) => $query->where('idempotency_key', $idempotencyKey))
            ->exists()) {
            return $next($request);
        }

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $retryAfterSeconds = max(1, $this->limiter->availableIn($key));
            $this->audit->recordBioGenerationRateLimit(
                $userId,
                $maxAttempts,
                $decaySeconds,
                $retryAfterSeconds,
            );

            return response()->json([
                'success' => false,
                'message' => 'Muitas sugestões em pouco tempo. Aguarde e tente novamente.',
                'code' => 'rate_limited',
                'retry_after_seconds' => $retryAfterSeconds,
                'errors' => (object) [],
            ], 429)->header('Retry-After', (string) $retryAfterSeconds);
        }

        $this->limiter->hit($key, $decaySeconds);

        return $next($request);
    }
}
