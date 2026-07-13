<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * @wiki app/brain/services/DiscoveryCache.md
 */
class DiscoveryCache
{
    /**
     * @wiki app/brain/functions/DiscoveryCache.md#remember
     */
    public function remember(string $surface, int $userId, array $filters, Closure $load): mixed
    {
        ksort($filters);
        $version = (int) Cache::get('discovery:version', 1);

        $key = sprintf(
            'discovery:v%d:%s:%d:%s',
            $version,
            $surface,
            $userId,
            hash('sha256', json_encode($filters, JSON_THROW_ON_ERROR)),
        );

        return Cache::remember(
            $key,
            max(1, (int) config('discovery.cache_ttl_seconds')),
            $load,
        );
    }
}
