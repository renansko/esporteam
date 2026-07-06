<?php

use App\Enums\ClusteringRunStatus;
use App\Models\ClusteringRun;

it('casts status to enum', function () {
    $run = new ClusteringRun(['workspace_id' => 1, 'status' => 'done', 'started_at' => now()]);
    expect($run->status)->toBe(ClusteringRunStatus::Done);
});

it('casts datetime fields', function () {
    $run = new ClusteringRun([
        'workspace_id' => 1,
        'status'       => 'running',
        'started_at'   => '2026-05-24 10:00:00',
    ]);
    expect($run->started_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('casts fallback_used to bool', function () {
    $run = new ClusteringRun([
        'workspace_id'  => 1,
        'status'        => 'done',
        'started_at'    => now(),
        'fallback_used' => 1,
    ]);
    expect($run->fallback_used)->toBeTrue();
});
