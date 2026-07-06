<?php

namespace App\Services\Clustering;

use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Circuit breaker leve por workspace (count em janela deslizante via Cache).
 *
 * Aberto = pular LLM e ir direto pro fallback até a janela passar.
 * Não usa Redis explicitamente — depende do CACHE_STORE configurado;
 * em prod 'redis', em testes 'array'.
 */
class CircuitBreaker
{
    public function __construct(
        private readonly Cache $cache,
        private readonly int $threshold = 5,
        private readonly int $windowSeconds = 300,
    ) {}

    private function key(int $workspaceId): string
    {
        return "clustering:circuit:{$workspaceId}";
    }

    public function isOpen(int $workspaceId): bool
    {
        return $this->failureCount($workspaceId) >= $this->threshold;
    }

    public function failureCount(int $workspaceId): int
    {
        return (int) $this->cache->get($this->key($workspaceId), 0);
    }

    public function recordFailure(int $workspaceId): int
    {
        $key = $this->key($workspaceId);
        $count = $this->failureCount($workspaceId) + 1;
        $this->cache->put($key, $count, $this->windowSeconds);
        return $count;
    }

    public function recordSuccess(int $workspaceId): void
    {
        $this->cache->forget($this->key($workspaceId));
    }
}
