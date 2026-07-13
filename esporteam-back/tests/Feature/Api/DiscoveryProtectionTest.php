<?php

use App\Services\DiscoveryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

it('caches a discovery response by authenticated user and filters', function () {
    Cache::flush();

    $discovery = Mockery::mock(DiscoveryService::class);
    $discovery->shouldReceive('discoverForUser')
        ->once()
        ->with(9101, ['mode' => 'sessions'])
        ->andReturn([
            'mode' => 'sessions',
            'cards' => new Collection,
            'empty_state' => null,
        ]);
    app()->instance(DiscoveryService::class, $discovery);

    actingAsWorkspace(1, ['id' => 9101])->getJson('/api/discovery?mode=sessions')->assertOk();
    actingAsWorkspace(1, ['id' => 9101])->getJson('/api/discovery?mode=sessions')->assertOk();
});

it('limits discovery requests per authenticated user', function () {
    config()->set('discovery.rate_limits.discovery_per_minute', 1);

    actingAsWorkspace(1, ['id' => 9102])->getJson('/api/discovery')->assertOk();
    actingAsWorkspace(1, ['id' => 9102])->getJson('/api/discovery')->assertTooManyRequests();
});

it('limits map session requests independently per authenticated user', function () {
    config()->set('discovery.rate_limits.map_per_minute', 1);

    actingAsWorkspace(1, ['id' => 9103])->getJson('/api/sessions')->assertOk();
    actingAsWorkspace(1, ['id' => 9103])->getJson('/api/sessions')->assertTooManyRequests();
});
