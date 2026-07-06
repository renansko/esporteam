<?php

use App\Enums\ClusteringDecisionAction;
use App\Models\ClusteringDecision;

it('casts action to enum', function () {
    $decision = new ClusteringDecision([
        'run_id'          => 1,
        'idea_id'         => 1,
        'roadmap_item_id' => 1,
        'action'          => 'assign',
        'rationale'       => 'matches existing item',
    ]);
    expect($decision->action)->toBe(ClusteringDecisionAction::Assign);
});

it('has no UPDATED_AT timestamp', function () {
    expect(ClusteringDecision::UPDATED_AT)->toBeNull();
});
