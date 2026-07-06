<?php

use App\Enums\ClusteringDecisionAction;
use App\Models\ClusteringDecision;
use App\Models\ClusteringRun;
use App\Models\Idea;
use App\Models\RoadmapItem;

it('returns drilldown with ideas and cluster_decision nested', function () {
    $item = RoadmapItem::factory()->create(['workspace_id' => 1]);
    $idea = Idea::factory()->create(['workspace_id' => 1, 'roadmap_item_id' => $item->id]);
    $run  = ClusteringRun::factory()->create(['workspace_id' => 1]);
    ClusteringDecision::create([
        'run_id'          => $run->id,
        'idea_id'         => $idea->id,
        'roadmap_item_id' => $item->id,
        'action'          => ClusteringDecisionAction::Create->value,
        'rationale'       => 'why',
    ]);

    $resp = actingAsWorkspace(1)->getJson("/api/roadmap/{$item->id}")->assertOk();
    expect($resp->json('data.id'))->toBe($item->id)
        ->and($resp->json('data.ideas.0.id'))->toBe($idea->id)
        ->and($resp->json('data.ideas.0.cluster_decision.rationale'))->toBe('why')
        ->and($resp->json('data.ideas.0.cluster_decision.run_id'))->toBe($run->id);
});

it('returns null cluster_decision for manually attached ideas', function () {
    $item = RoadmapItem::factory()->create(['workspace_id' => 1]);
    Idea::factory()->create(['workspace_id' => 1, 'roadmap_item_id' => $item->id]);

    $resp = actingAsWorkspace(1)->getJson("/api/roadmap/{$item->id}")->assertOk();
    expect($resp->json('data.ideas.0.cluster_decision'))->toBeNull();
});

it('returns 404 when item belongs to other workspace', function () {
    $item = RoadmapItem::factory()->create(['workspace_id' => 99]);
    actingAsWorkspace(1)->getJson("/api/roadmap/{$item->id}")->assertNotFound();
});
