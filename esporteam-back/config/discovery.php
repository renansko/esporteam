<?php

return [
    'cache_ttl_seconds' => (int) env('DISCOVERY_CACHE_TTL_SECONDS', 30),
    'rate_limits' => [
        'discovery_per_minute' => (int) env('DISCOVERY_RATE_LIMIT_PER_MINUTE', 30),
        'map_per_minute' => (int) env('MAP_RATE_LIMIT_PER_MINUTE', 60),
    ],
];
