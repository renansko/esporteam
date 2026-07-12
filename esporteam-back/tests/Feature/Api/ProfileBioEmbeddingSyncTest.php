<?php

use App\Jobs\GenerateProfileBioEmbedding;
use App\Models\ProfileBioEmbedding;
use App\Models\SportProfile;
use App\Services\Llm\Contracts\EmbeddingClient;
use App\Services\Llm\Contracts\EmbeddingRequest;
use App\Services\Llm\Contracts\EmbeddingResponse;
use App\Services\Llm\Drivers\FakeEmbeddingClient;
use App\Services\ProfileBioEmbeddingGenerationService;
use App\Services\ProfileBioEmbeddingService;
use Illuminate\Support\Facades\Bus;

it('invalidates and queues an embedding after a manual bio edit', function () {
    Bus::fake();

    actingAsWorkspace(1, ['id' => 901])
        ->putJson('/api/profile', [
            'display_name' => 'Perfil manual',
            'bio' => 'Jogo tenis aos sabados.',
        ])
        ->assertOk();

    $record = ProfileBioEmbedding::query()->sole();
    expect($record->status)->toBe('pending')
        ->and($record->source_hash)->toBe(hash('sha256', 'Jogo tenis aos sabados.'));

    Bus::assertDispatched(GenerateProfileBioEmbedding::class);
});

it('removes the active embedding and does not queue empty bios', function () {
    Bus::fake();
    $profile = SportProfile::query()->create([
        'user_id' => 902,
        'display_name' => 'Perfil limpo',
        'bio' => 'Bio antiga.',
    ]);
    ProfileBioEmbedding::query()->create([
        'sport_profile_id' => $profile->id,
        'status' => 'completed',
        'source_hash' => hash('sha256', $profile->bio),
        'embedding' => array_fill(0, 1536, 0.1),
    ]);

    actingAsWorkspace(1, ['id' => 902])
        ->putJson('/api/profile', [
            'display_name' => 'Perfil limpo',
            'bio' => '   ',
        ])
        ->assertOk();

    expect(ProfileBioEmbedding::query()->count())->toBe(0);
    Bus::assertNotDispatched(GenerateProfileBioEmbedding::class);
});

it('cannot commit a result after the bio changes while the provider is running', function () {
    Bus::fake();
    $profile = SportProfile::query()->create([
        'user_id' => 903,
        'display_name' => 'Perfil concorrente',
        'bio' => 'Bio antiga.',
    ]);
    $oldHash = hash('sha256', $profile->bio);
    app(ProfileBioEmbeddingService::class)->synchronize($profile);
    Bus::fake();

    $embedding = new FakeEmbeddingClient;
    $embedding->intercept(function (EmbeddingRequest $request) use ($profile) {
        $profile->update(['bio' => 'Bio nova.']);
        app(ProfileBioEmbeddingService::class)->synchronize($profile->fresh());

        return new EmbeddingResponse([array_fill(0, 1536, 0.2)], 'fake', 1);
    });
    app()->instance(EmbeddingClient::class, $embedding);

    (new GenerateProfileBioEmbedding($profile->id, $oldHash))->handle(app(ProfileBioEmbeddingGenerationService::class));

    $record = ProfileBioEmbedding::query()->sole();
    expect($record->source_hash)->toBe(hash('sha256', 'Bio nova.'))
        ->and($record->status)->toBe('pending')
        ->and($record->embedding)->toBeNull();
});
