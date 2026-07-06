<?php

use App\Enums\IdeaSource;
use App\Models\Idea;
use App\Models\RoadmapItem;

beforeEach(function () {
    $roadmapItem = RoadmapItem::factory()->create(['workspace_id' => 10]);

    // Workspace 10 — 3 ideias, fontes variadas
    Idea::create(['workspace_id' => 10, 'source' => IdeaSource::Manual,     'description' => 'A1']);
    Idea::create(['workspace_id' => 10, 'source' => IdeaSource::Csv,        'description' => 'A2']);
    Idea::create(['workspace_id' => 10, 'source' => IdeaSource::PublicForm, 'description' => 'A3', 'roadmap_item_id' => $roadmapItem->id]);

    // Workspace 20 — 3 ideias, devem NUNCA aparecer pra workspace 10
    Idea::create(['workspace_id' => 20, 'source' => IdeaSource::Manual, 'description' => 'B1']);
    Idea::create(['workspace_id' => 20, 'source' => IdeaSource::Csv,    'description' => 'B2']);
    Idea::create(['workspace_id' => 20, 'source' => IdeaSource::Manual, 'description' => 'B3']);
});

it('lists only ideas from the authenticated workspace (cross-tenant isolation)', function () {
    $response = actingAsWorkspace(10)
        ->getJson('/api/ideas')
        ->assertOk()
        ->assertJson(['success' => true]);

    $data = $response->json('data');
    expect($data)->toHaveCount(3);

    $descriptions = collect($data)->pluck('description')->all();
    sort($descriptions);
    expect($descriptions)->toBe(['A1', 'A2', 'A3']);

    // Nenhuma do workspace 20 vaza
    foreach ($data as $row) {
        expect($row['description'])->not->toStartWith('B');
    }
});

it('orders by created_at desc', function () {
    $response = actingAsWorkspace(20)
        ->getJson('/api/ideas')
        ->assertOk();

    $data = $response->json('data');
    // 3 ideias do workspace 20, mais nova primeiro
    expect($data[0]['description'])->toBe('B3')
        ->and($data[2]['description'])->toBe('B1');
});

it('filters by ?source=', function () {
    $response = actingAsWorkspace(10)
        ->getJson('/api/ideas?source=csv')
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(1)
        ->and($data[0]['source'])->toBe('csv');
});

it('filters by ?unclustered=true', function () {
    $response = actingAsWorkspace(10)
        ->getJson('/api/ideas?unclustered=true')
        ->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(2); // A3 está clusterizado (roadmap_item_id=999)
    foreach ($data as $row) {
        expect($row['clustered'])->toBeFalse();
    }
});

it('paginates with per_page', function () {
    $response = actingAsWorkspace(10)
        ->getJson('/api/ideas?per_page=2&page=1')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2)
        ->and($response->json('meta.current_page'))->toBe(1)
        ->and($response->json('meta.per_page'))->toBe(2)
        ->and($response->json('meta.total'))->toBe(3);
});
