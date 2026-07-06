<?php

use App\Models\Idea;
use App\Services\Llm\Contracts\EmbeddingClient;
use App\Services\Llm\Drivers\FakeEmbeddingClient;

it('attaches embedding to every Idea with embedding null', function () {
    $fake = new FakeEmbeddingClient();
    app()->instance(EmbeddingClient::class, $fake);

    Idea::factory()->count(3)->create(['workspace_id' => 5]);

    $this->artisan('ideas:backfill-embeddings')
        ->expectsOutputToContain('3 idea(s) processed.')
        ->assertSuccessful();

    expect(Idea::whereNotNull('embedding')->count())->toBe(3);
});

it('limits to a single workspace when --workspace= passed', function () {
    app()->instance(EmbeddingClient::class, new FakeEmbeddingClient());

    Idea::factory()->count(2)->create(['workspace_id' => 10]);
    Idea::factory()->count(2)->create(['workspace_id' => 20]);

    $this->artisan('ideas:backfill-embeddings', ['--workspace' => 10])
        ->expectsOutputToContain('2 idea(s) processed.')
        ->assertSuccessful();

    expect(Idea::where('workspace_id', 10)->whereNotNull('embedding')->count())->toBe(2);
    expect(Idea::where('workspace_id', 20)->whereNotNull('embedding')->count())->toBe(0);
});
