<?php

use App\Enums\IdeaSource;
use App\Models\Idea;
use App\Services\IdeaIngestionService;
use App\Services\IngestIdeaInput;

it('ingests an idea normalizing author_email', function () {
    $service = app(IdeaIngestionService::class);

    $idea = $service->ingest(new IngestIdeaInput(
        workspaceId: 7,
        source: IdeaSource::Manual,
        description: 'Quero poder exportar relatórios',
        title: null,
        authorEmail: '  Foo@BAR.com ',
    ));

    expect($idea)->toBeInstanceOf(Idea::class)
        ->and($idea->workspace_id)->toBe(7)
        ->and($idea->source)->toBe(IdeaSource::Manual)
        ->and($idea->description)->toBe('Quero poder exportar relatórios')
        ->and($idea->author_email)->toBe('foo@bar.com');
});

it('accepts a null author_email', function () {
    $service = app(IdeaIngestionService::class);

    $idea = $service->ingest(new IngestIdeaInput(
        workspaceId: 1,
        source: IdeaSource::Csv,
        description: 'algo',
    ));

    expect($idea->author_email)->toBeNull();
});
