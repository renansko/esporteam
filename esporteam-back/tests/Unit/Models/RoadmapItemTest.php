<?php

use App\Enums\RoadmapItemOrigin;
use App\Enums\RoadmapItemStatus;
use App\Enums\RoadmapItemVisibility;
use App\Models\RoadmapItem;

it('casts enum columns', function () {
    $item = new RoadmapItem([
        'workspace_id' => 1,
        'title'        => 'x',
        'description'  => 'y',
        'status'       => 'planejado',
        'visibility'   => 'public',
        'origin'       => 'clustered',
    ]);

    expect($item->status)->toBe(RoadmapItemStatus::Planejado)
        ->and($item->visibility)->toBe(RoadmapItemVisibility::Public)
        ->and($item->origin)->toBe(RoadmapItemOrigin::Clustered);
});

it('casts score_breakdown to array', function () {
    $item = new RoadmapItem([
        'workspace_id'    => 1,
        'title'           => 'x',
        'description'     => 'y',
        'score_breakdown' => ['impact' => 4, 'reach' => 3, 'effort' => 2],
    ]);

    expect($item->score_breakdown)->toBe(['impact' => 4, 'reach' => 3, 'effort' => 2]);
});

it('recomputes score from RICE breakdown', function () {
    $item = new RoadmapItem(['workspace_id' => 1, 'title' => 'x', 'description' => 'y']);
    $item->score_breakdown = ['impact' => 4, 'reach' => 3, 'effort' => 2];
    $item->recomputeScore();
    expect((float) $item->score)->toBe(6.0); // (4*3)/2
});

it('coerces effort=0 to 1 to avoid division by zero', function () {
    $item = new RoadmapItem(['workspace_id' => 1, 'title' => 'x', 'description' => 'y']);
    $item->score_breakdown = ['impact' => 3, 'reach' => 3, 'effort' => 0];
    $item->recomputeScore();
    expect((float) $item->score)->toBe(9.0); // (3*3)/1
});

it('recompute handles fallback breakdown {3,3,3}', function () {
    $item = new RoadmapItem(['workspace_id' => 1, 'title' => 'x', 'description' => 'y']);
    $item->score_breakdown = ['impact' => 3, 'reach' => 3, 'effort' => 3];
    $item->recomputeScore();
    expect((float) $item->score)->toBe(3.0);
});

it('recompute keeps 4 decimal precision', function () {
    $item = new RoadmapItem(['workspace_id' => 1, 'title' => 'x', 'description' => 'y']);
    $item->score_breakdown = ['impact' => 5, 'reach' => 4, 'effort' => 3];
    $item->recomputeScore();
    expect((float) $item->score)->toBe(6.6667);
});
