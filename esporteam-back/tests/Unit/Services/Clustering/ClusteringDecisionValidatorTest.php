<?php

use App\Services\Clustering\ClusteringDecisionValidator;

it('accepts valid assign decision', function () {
    $v = new ClusteringDecisionValidator();
    $out = $v->validate(
        ['idea_id' => 1, 'action' => 'assign', 'roadmap_item_id' => 7, 'rationale' => 'match'],
        allowedIdeaIds: [1, 2],
        allowedItemIds: [7]
    );
    expect($out)->toMatchArray(['action' => 'assign', 'roadmap_item_id' => 7]);
});

it('accepts valid create decision', function () {
    $v = new ClusteringDecisionValidator();
    $out = $v->validate(
        [
            'idea_id'  => 1,
            'action'   => 'create',
            'new_item' => ['title' => 'T', 'description' => 'D', 'impact' => 3, 'reach' => 4, 'effort' => 2],
            'rationale'=> 'new theme',
        ],
        [1], []
    );
    expect($out['action'])->toBe('create')
        ->and($out['new_item']['score_breakdown'])->toBe(['impact' => 3, 'reach' => 4, 'effort' => 2]);
});

it('rejects idea_id not in workspace', function () {
    $v = new ClusteringDecisionValidator();
    $out = $v->validate(['idea_id' => 99, 'action' => 'assign', 'roadmap_item_id' => 7], [1, 2], [7]);
    expect($out)->toBeNull();
});

it('rejects assign without roadmap_item_id', function () {
    $v = new ClusteringDecisionValidator();
    $out = $v->validate(['idea_id' => 1, 'action' => 'assign'], [1], [7]);
    expect($out)->toBeNull();
});

it('rejects create with score out of [1,5]', function () {
    $v = new ClusteringDecisionValidator();
    $out = $v->validate(
        ['idea_id' => 1, 'action' => 'create', 'new_item' => ['title' => 'T', 'description' => 'D', 'impact' => 7, 'reach' => 3, 'effort' => 2]],
        [1], []
    );
    expect($out)->toBeNull();
});

it('coerces effort=0 to 1 with warning', function () {
    $v = new ClusteringDecisionValidator();
    $out = $v->validate(
        ['idea_id' => 1, 'action' => 'create', 'new_item' => ['title' => 'T', 'description' => 'D', 'impact' => 3, 'reach' => 3, 'effort' => 0]],
        [1], []
    );
    expect($out['new_item']['score_breakdown']['effort'])->toBe(1)
        ->and($v->warnings)->toContain('coerced: effort=0 to 1');
});

it('rejects create with empty title or description', function () {
    $v = new ClusteringDecisionValidator();
    $out = $v->validate(
        ['idea_id' => 1, 'action' => 'create', 'new_item' => ['title' => '', 'description' => 'D', 'impact' => 3, 'reach' => 3, 'effort' => 2]],
        [1], []
    );
    expect($out)->toBeNull();
});

it('rejects unknown action', function () {
    $v = new ClusteringDecisionValidator();
    $out = $v->validate(['idea_id' => 1, 'action' => 'delete'], [1], [7]);
    expect($out)->toBeNull();
});
