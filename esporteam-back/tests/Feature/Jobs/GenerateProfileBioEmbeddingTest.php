<?php

use App\Jobs\GenerateProfileBioEmbedding;
use App\Models\ProfileBioEmbedding;
use App\Models\SportProfile;
use App\Services\Llm\Contracts\EmbeddingClient;
use App\Services\Llm\Contracts\EmbeddingRequest;
use App\Services\Llm\Contracts\EmbeddingResponse;
use App\Services\Llm\Drivers\FakeEmbeddingClient;

it('stores a 1536-dimensional embedding derived only from the current bio', function () {
    $profile = SportProfile::query()->create(['user_id' => 811, 'display_name' => 'Embedding']);
    $profile->update(['bio' => 'Pratico corrida nas manhãs de sábado.']);
    $embedding = new FakeEmbeddingClient;
    app()->instance(EmbeddingClient::class, $embedding);

    (new GenerateProfileBioEmbedding($profile->id, hash('sha256', $profile->bio)))->handle(app(EmbeddingClient::class));

    $record = ProfileBioEmbedding::query()->sole();
    expect($record->status)->toBe('completed')
        ->and($record->source_hash)->toBe(hash('sha256', $profile->bio))
        ->and($record->model)->toBe('fake-embedding')
        ->and($record->embedded_at)->not->toBeNull()
        ->and($record->embedding)->toHaveCount(1536)
        ->and($embedding->calls())->toHaveCount(1)
        ->and($embedding->calls()[0]->inputs)->toBe([$profile->bio]);
});

it('records a sanitized embedding failure without changing the accepted bio', function () {
    $profile = SportProfile::query()->create(['user_id' => 812, 'display_name' => 'Falha embedding', 'bio' => 'Bio aceita.']);
    $embedding = new FakeEmbeddingClient;
    $embedding->intercept(fn (EmbeddingRequest $request) => throw new RuntimeException('private provider payload'));
    app()->instance(EmbeddingClient::class, $embedding);

    try {
        (new GenerateProfileBioEmbedding($profile->id, hash('sha256', $profile->bio)))->handle(app(EmbeddingClient::class));
    } catch (RuntimeException) {
        // Queue workers rethrow provider errors so Laravel can retry them.
    }

    $record = ProfileBioEmbedding::query()->sole();
    expect($profile->fresh()->bio)->toBe('Bio aceita.')
        ->and($record->status)->toBe('failed')
        ->and($record->failure_code)->toBe('provider_unavailable')
        ->and($record->metadata)->not->toHaveKey('raw_error');
});

it('rejects vectors with a dimension other than 1536', function () {
    $profile = SportProfile::query()->create(['user_id' => 813, 'display_name' => 'Dimensão', 'bio' => 'Bio aceita.']);
    $embedding = new FakeEmbeddingClient;
    $embedding->intercept(fn (EmbeddingRequest $request) => new EmbeddingResponse([[0.1, 0.2]], 'bad-model', 1));
    app()->instance(EmbeddingClient::class, $embedding);

    (new GenerateProfileBioEmbedding($profile->id, hash('sha256', $profile->bio)))->handle(app(EmbeddingClient::class));

    expect(ProfileBioEmbedding::query()->sole()->status)->toBe('failed')
        ->and(ProfileBioEmbedding::query()->sole()->failure_code)->toBe('invalid_vector');
});

it('is safe to retry after completing an embedding', function () {
    $profile = SportProfile::query()->create(['user_id' => 814, 'display_name' => 'Retry', 'bio' => 'Bio aceita.']);
    $embedding = new FakeEmbeddingClient;
    app()->instance(EmbeddingClient::class, $embedding);
    $job = new GenerateProfileBioEmbedding($profile->id, hash('sha256', $profile->bio));

    $job->handle(app(EmbeddingClient::class));
    $job->handle(app(EmbeddingClient::class));

    expect(ProfileBioEmbedding::query()->count())->toBe(1)
        ->and(ProfileBioEmbedding::query()->sole()->status)->toBe('completed')
        ->and($embedding->calls())->toHaveCount(1);
});
