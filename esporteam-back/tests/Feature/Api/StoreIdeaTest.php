<?php

use App\Enums\IdeaSource;
use App\Models\Idea;

it('creates an idea from the JWT workspace claim with source=manual', function () {
    actingAsWorkspace(42)
        ->postJson('/api/ideas', [
            'description'  => 'Quero exportar dashboards em PDF',
            'author_email' => 'pm@acme.test',
        ])
        ->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Idea created.',
            'data'    => [
                'source'       => 'manual',
                'description'  => 'Quero exportar dashboards em PDF',
                'author_email' => 'pm@acme.test',
                'title'        => null,
                'clustered'    => false,
            ],
        ]);

    $idea = Idea::first();
    expect($idea)->not->toBeNull()
        ->and($idea->workspace_id)->toBe(42)
        ->and($idea->source)->toBe(IdeaSource::Manual);
});

it('returns 422 with envelope when description is missing', function () {
    actingAsWorkspace(1)
        ->postJson('/api/ideas', ['title' => 'só título'])
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => ['description'],
        ]);

    expect(Idea::count())->toBe(0);
});

it('returns 422 when author_email is not a valid email', function () {
    actingAsWorkspace(1)
        ->postJson('/api/ideas', [
            'description'  => 'algo',
            'author_email' => 'nope',
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['author_email']]);
});

it('ignores workspace_id sent in the body — claim wins', function () {
    actingAsWorkspace(99)
        ->postJson('/api/ideas', [
            'description'  => 'tentativa de spoof',
            'workspace_id' => 1, // deve ser ignorado
        ])
        ->assertCreated();

    expect(Idea::first()->workspace_id)->toBe(99);
});
