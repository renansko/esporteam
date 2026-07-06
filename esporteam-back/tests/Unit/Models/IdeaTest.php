<?php

use App\Enums\IdeaSource;
use App\Models\Idea;

it('casts source to IdeaSource enum', function () {
    $idea = new Idea(['source' => 'manual', 'description' => 'x', 'workspace_id' => 1]);
    expect($idea->source)->toBeInstanceOf(IdeaSource::class)
        ->and($idea->source)->toBe(IdeaSource::Manual);
});

it('normalizes author_email to lowercase and trims', function () {
    $idea = new Idea();
    $idea->author_email = '  John@Example.COM ';
    expect($idea->author_email)->toBe('john@example.com');
});

it('persists workspace_id as a raw column (no relationship)', function () {
    // Garantir que NÃO há método workspace() — escolha explícita do grill.
    expect(method_exists(Idea::class, 'workspace'))->toBeFalse();
});
