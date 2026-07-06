<?php

use App\Enums\ClusteringRunStatus;
use App\Models\ClusteringRun;

it('marks stuck runs as failed', function () {
    config()->set('llm.watchdog_timeout_seconds', 600);

    $oldRun = ClusteringRun::factory()->create([
        'workspace_id' => 1,
        'status' => ClusteringRunStatus::Running->value,
        'started_at' => now()->subMinutes(20),
    ]);
    $freshRun = ClusteringRun::factory()->create([
        'workspace_id' => 2,
        'status' => ClusteringRunStatus::Running->value,
        'started_at' => now()->subSeconds(10),
    ]);

    $this->artisan('clustering:watchdog')
        ->expectsOutputToContain('Watchdog: 1 run(s) killed.')
        ->assertSuccessful();

    expect($oldRun->fresh()->status)->toBe(ClusteringRunStatus::Failed)
        ->and($oldRun->fresh()->failure_reason)->toContain('watchdog timeout')
        ->and($freshRun->fresh()->status)->toBe(ClusteringRunStatus::Running);
});

it('does nothing when no runs are stuck', function () {
    $this->artisan('clustering:watchdog')
        ->expectsOutputToContain('Watchdog: 0 run(s) killed.')
        ->assertSuccessful();
});
