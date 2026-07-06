<?php

use App\Models\RoadmapItem;

it('isolates by workspace (cross-tenant)', function () {
    RoadmapItem::factory()->count(2)->create(['workspace_id' => 1]);
    RoadmapItem::factory()->count(3)->create(['workspace_id' => 2]);

    actingAsWorkspace(1)->getJson('/api/roadmap')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('orders by score DESC, votes_count DESC, id DESC', function () {
    RoadmapItem::factory()->create(['workspace_id' => 1, 'score' => 1.0, 'votes_count' => 0]);
    $top = RoadmapItem::factory()->create(['workspace_id' => 1, 'score' => 9.0, 'votes_count' => 5]);
    RoadmapItem::factory()->create(['workspace_id' => 1, 'score' => 5.0, 'votes_count' => 10]);

    $resp = actingAsWorkspace(1)->getJson('/api/roadmap')->assertOk();
    expect($resp->json('data.0.id'))->toBe($top->id);
});

it('filters by status, origin, visibility', function () {
    RoadmapItem::factory()->create(['workspace_id' => 1, 'status' => 'planejado', 'origin' => 'manual', 'visibility' => 'internal']);
    RoadmapItem::factory()->create(['workspace_id' => 1, 'status' => 'lancado',   'origin' => 'clustered', 'visibility' => 'public']);

    actingAsWorkspace(1)->getJson('/api/roadmap?status=planejado')
        ->assertOk()->assertJsonCount(1, 'data');
    actingAsWorkspace(1)->getJson('/api/roadmap?visibility=public')
        ->assertOk()->assertJsonCount(1, 'data');
    actingAsWorkspace(1)->getJson('/api/roadmap?origin=clustered')
        ->assertOk()->assertJsonCount(1, 'data');
});

it('returns 422 for invalid filter values', function () {
    actingAsWorkspace(1)->getJson('/api/roadmap?status=invalid')
        ->assertStatus(422)
        ->assertJsonStructure(['success', 'message', 'errors' => ['status']]);
});

it('exposes ideas_count via withCount', function () {
    $item = RoadmapItem::factory()->create(['workspace_id' => 1]);
    \App\Models\Idea::factory()->count(3)->create(['workspace_id' => 1, 'roadmap_item_id' => $item->id]);

    $resp = actingAsWorkspace(1)->getJson('/api/roadmap')->assertOk();
    expect($resp->json('data.0.ideas_count'))->toBe(3);
});
