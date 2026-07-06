<?php

use App\Models\Idea;
use App\Services\Clustering\ClusteringPreClusterStrategy;

function fakeIdeaWith(?array $vector, int $id = 0): Idea
{
    $i = new Idea(['workspace_id' => 1, 'source' => 'manual', 'description' => 'x']);
    $i->id = $id;
    if ($vector !== null) {
        $i->setAttribute('embedding', $vector);
    }
    return $i;
}

it('bundles ideias with cosine similarity >= threshold', function () {
    // Vetores quase idênticos.
    $a = [1.0, 0.0, 0.0];
    $b = [0.99, 0.05, 0.05];
    $c = [0.0, 1.0, 0.0]; // ortogonal a A/B

    $strategy = new ClusteringPreClusterStrategy(threshold: 0.85, maxBundleSize: 10);
    $bundles = $strategy->bundle(collect([
        fakeIdeaWith($a, 1),
        fakeIdeaWith($b, 2),
        fakeIdeaWith($c, 3),
    ]));

    expect($bundles)->toHaveCount(2);
    expect($bundles[0]->ideaIds())->toEqual([1, 2]);
    expect($bundles[1]->ideaIds())->toEqual([3]);
});

it('creates singleton bundles when no embeddings are available', function () {
    $strategy = new ClusteringPreClusterStrategy();
    $bundles = $strategy->bundle(collect([
        fakeIdeaWith(null, 10),
        fakeIdeaWith(null, 11),
    ]));
    expect($bundles)->toHaveCount(2)
        ->and($bundles[0]->size())->toBe(1)
        ->and($bundles[1]->size())->toBe(1);
});

it('respects maxBundleSize', function () {
    $strategy = new ClusteringPreClusterStrategy(threshold: 0.0, maxBundleSize: 2);
    $ideas = collect([
        fakeIdeaWith([1.0, 0, 0], 1),
        fakeIdeaWith([1.0, 0, 0], 2),
        fakeIdeaWith([1.0, 0, 0], 3),
    ]);
    $bundles = $strategy->bundle($ideas);
    // 1 representante + 1 sibling = 2 (limite). O 3 sobra para outro bundle.
    expect($bundles)->toHaveCount(2);
    expect($bundles[0]->size())->toBe(2);
});
