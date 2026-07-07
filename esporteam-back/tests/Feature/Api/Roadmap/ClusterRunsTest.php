<?php

use App\Enums\ClusteringDecisionAction;
use App\Enums\ClusteringRunStatus;
use App\Jobs\ClusterIdeasJob;
use App\Models\ClusteringDecision;
use App\Models\ClusteringRun;
use App\Models\Idea;
use App\Models\RoadmapItem;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    config()->set('llm.rate_limit.max_attempts', 1000);
});

it('dispatches a new run and returns 202 with run_id', function () {
    Bus::fake();
    Idea::factory()->create(['workspace_id' => 1]);

    $resp = actingAsWorkspace(1)->postJson('/api/roadmap/cluster')
        ->assertStatus(202)
        ->assertJson(['success' => true]);
    expect($resp->json('data.status'))->toBe('running');
    Bus::assertDispatched(ClusterIdeasJob::class);
    expect(ClusteringRun::count())->toBe(1);
});

it('returns 409 when another run is already running for this workspace', function () {
    Bus::fake();
    ClusteringRun::factory()->create(['workspace_id' => 1, 'status' => ClusteringRunStatus::Running->value]);
    Idea::factory()->create(['workspace_id' => 1]);

    $resp = actingAsWorkspace(1)->postJson('/api/roadmap/cluster')
        ->assertStatus(409)
        ->assertJson(['success' => false]);
    expect($resp->json('errors.existing_run_id'))->toBeInt();
    Bus::assertNotDispatched(ClusterIdeasJob::class);
});

it('returns 402 when monthly token budget exceeded', function () {
    Bus::fake();
    config()->set('llm.monthly_token_budget_per_workspace', 100);
    ClusteringRun::factory()->create(['workspace_id' => 1, 'token_usage_in' => 80, 'token_usage_out' => 30]);
    Idea::factory()->create(['workspace_id' => 1]);

    actingAsWorkspace(1)->postJson('/api/roadmap/cluster')->assertStatus(402);
});

it('returns 422 when there are no unclustered ideas', function () {
    Bus::fake();
    actingAsWorkspace(1)->postJson('/api/roadmap/cluster')->assertStatus(422);
});

it('lists runs filtered by status and fallback_used with cursor pagination', function () {
    ClusteringRun::factory()->count(3)->done()->create(['workspace_id' => 1]);
    ClusteringRun::factory()->failed('boom')->create(['workspace_id' => 1]);
    ClusteringRun::factory()->done()->create(['workspace_id' => 1, 'fallback_used' => true]);
    ClusteringRun::factory()->create(['workspace_id' => 99]); // outro workspace

    $resp = actingAsWorkspace(1)->getJson('/api/roadmap/cluster/runs')->assertOk();
    expect($resp->json('data'))->toHaveCount(5);

    actingAsWorkspace(1)->getJson('/api/roadmap/cluster/runs?status=failed')
        ->assertOk()->assertJsonCount(1, 'data');
    actingAsWorkspace(1)->getJson('/api/roadmap/cluster/runs?fallback_used=true')
        ->assertOk()->assertJsonCount(1, 'data');
});

it('returns single run with cost_usd computed', function () {
    $run = ClusteringRun::factory()->done()->create([
        'workspace_id'    => 1,
        'token_usage_in'  => 1_000_000,
        'token_usage_out' => 1_000_000,
        'llm_model'       => 'claude-haiku-4-5-20251001',
    ]);

    $resp = actingAsWorkspace(1)->getJson("/api/roadmap/cluster/runs/{$run->id}")->assertOk();
    expect($resp->json('data.cost_usd'))->toBe(1.5);
});

it('isolates a single run by workspace (404 for other tenant)', function () {
    $run = ClusteringRun::factory()->create(['workspace_id' => 99]);
    actingAsWorkspace(1)->getJson("/api/roadmap/cluster/runs/{$run->id}")->assertNotFound();
});

it('lists decisions of a run with nested idea + roadmap_item', function () {
    $run  = ClusteringRun::factory()->create(['workspace_id' => 1]);
    $idea = Idea::factory()->create(['workspace_id' => 1]);
    $item = RoadmapItem::factory()->create(['workspace_id' => 1]);
    ClusteringDecision::create([
        'run_id'          => $run->id,
        'idea_id'         => $idea->id,
        'roadmap_item_id' => $item->id,
        'action'          => ClusteringDecisionAction::Assign->value,
        'rationale'       => 'fits',
    ]);

    $resp = actingAsWorkspace(1)->getJson("/api/roadmap/cluster/runs/{$run->id}/decisions")->assertOk();
    expect($resp->json('data.0.idea.id'))->toBe($idea->id)
        ->and($resp->json('data.0.roadmap_item.id'))->toBe($item->id)
        ->and($resp->json('data.0.action'))->toBe('assign');
});
