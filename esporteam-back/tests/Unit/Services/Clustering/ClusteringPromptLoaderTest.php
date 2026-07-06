<?php

use App\Models\Idea;
use App\Models\RoadmapItem;
use App\Services\Clustering\ClusteringPromptLoader;

it('renders template substituting placeholders', function () {
    $loader = new ClusteringPromptLoader();
    $item = new RoadmapItem(['workspace_id' => 1, 'title' => 'Exportar dados', 'description' => 'Lorem ipsum.']);
    $item->id = 7;
    $items = collect([$item]);
    // Use unsaved Idea() with manually set id.
    $idea = new Idea(['workspace_id' => 1, 'title' => 'Baixar tabela', 'description' => 'Baixar como CSV']);
    $idea->id = 42;
    $ideas = collect([$idea]);

    $rendered = $loader->render('clustering_v1', $items, $ideas);

    expect($rendered)
        ->toContain('#7 | Exportar dados |')
        ->toContain('42|Baixar tabela|Baixar como CSV')
        ->not->toContain('{{EXISTING_ITEMS}}')
        ->not->toContain('{{IDEAS_CSV}}');
});

it('shows fallback when no existing items', function () {
    $rendered = (new ClusteringPromptLoader())->render('clustering_v1', collect(), collect());
    expect($rendered)->toContain('(nenhum item existente)');
});

it('throws when prompt file missing', function () {
    (new ClusteringPromptLoader())->load('does-not-exist');
})->throws(RuntimeException::class);
