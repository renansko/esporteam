<?php

use App\Events\ClusteringRunCompleted;
use App\Models\ClusteringRun;
use Illuminate\Broadcasting\PrivateChannel;

it('broadcasts on private workspace channel', function () {
    $run = ClusteringRun::factory()->done()->create(['workspace_id' => 77]);
    $event = new ClusteringRunCompleted($run);

    $channels = $event->broadcastOn();
    expect($channels)->toHaveCount(1)
        ->and($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe('private-workspaces.77.clustering');
});

it('uses broadcastAs name clustering.run.completed', function () {
    $run = ClusteringRun::factory()->create();
    expect((new ClusteringRunCompleted($run))->broadcastAs())->toBe('clustering.run.completed');
});

it('serializes payload with run_id and metrics', function () {
    $run = ClusteringRun::factory()->done()->create([
        'workspace_id'   => 1,
        'items_created'  => 2,
        'items_assigned' => 3,
        'fallback_used'  => false,
    ]);

    $payload = (new ClusteringRunCompleted($run))->broadcastWith();
    expect($payload['run_id'])->toBe($run->id)
        ->and($payload['items_created'])->toBe(2)
        ->and($payload['items_assigned'])->toBe(3)
        ->and($payload['fallback_used'])->toBeFalse()
        ->and($payload['status'])->toBe('done');
});
