<?php

use App\Enums\BioSuggestionStatus;
use App\Jobs\GenerateProfileBioEmbedding;
use App\Models\BioSuggestion;
use App\Models\SportProfile;
use App\Services\BioSuggestionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Bus;

function acceptanceSportProfile(int $userId, string $name = 'Perfil Bio'): SportProfile
{
    return SportProfile::query()->create([
        'user_id' => $userId,
        'display_name' => $name,
        'bio' => 'Bio original não deve ser alterada.',
    ]);
}

function generatedBioSuggestion(SportProfile $profile, string $bio = 'Pratico tênis e busco novas partidas.'): BioSuggestion
{
    return BioSuggestion::query()->create([
        'sport_profile_id' => $profile->id,
        'status' => BioSuggestionStatus::Generated,
        'generated_bio' => $bio,
        'structured_output' => ['bio' => $bio, 'key_points' => []],
        'prompt_version' => 'bio_v1',
    ]);
}

it('accepts an owned suggestion and dispatches its embedding after the bio is saved', function () {
    Bus::fake();
    $profile = acceptanceSportProfile(801);
    $suggestion = generatedBioSuggestion($profile);

    actingAsWorkspace(1, ['id' => 801])
        ->postJson("/api/profile/bio-suggestions/{$suggestion->id}/accept")
        ->assertOk()
        ->assertJsonPath('data.status', 'accepted')
        ->assertJsonPath('data.bio', 'Pratico tênis e busco novas partidas.');

    expect($profile->fresh()->bio)->toBe('Pratico tênis e busco novas partidas.')
        ->and($suggestion->fresh()->status)->toBe(BioSuggestionStatus::Accepted);

    Bus::assertDispatched(GenerateProfileBioEmbedding::class, function (GenerateProfileBioEmbedding $job) use ($profile) {
        return $job->profileId === $profile->id
            && $job->sourceHash === hash('sha256', 'Pratico tênis e busco novas partidas.');
    });
});

it('does not accept another profile suggestion or alter either profile', function () {
    Bus::fake();
    $mine = acceptanceSportProfile(802);
    $other = acceptanceSportProfile(803, 'Outro perfil');
    $suggestion = generatedBioSuggestion($other, 'Bio privada de outro perfil.');

    actingAsWorkspace(1, ['id' => 802])
        ->postJson("/api/profile/bio-suggestions/{$suggestion->id}/accept")
        ->assertNotFound();

    expect($mine->fresh()->bio)->toBe('Bio original não deve ser alterada.')
        ->and($other->fresh()->bio)->toBe('Bio original não deve ser alterada.')
        ->and($suggestion->fresh()->status)->toBe(BioSuggestionStatus::Generated);
    Bus::assertNotDispatched(GenerateProfileBioEmbedding::class);
});

it('accepts a suggestion idempotently without dispatching a second embedding job', function () {
    Bus::fake();
    $profile = acceptanceSportProfile(804);
    $suggestion = generatedBioSuggestion($profile);

    actingAsWorkspace(1, ['id' => 804])
        ->postJson("/api/profile/bio-suggestions/{$suggestion->id}/accept")
        ->assertOk();
    actingAsWorkspace(1, ['id' => 804])
        ->postJson("/api/profile/bio-suggestions/{$suggestion->id}/accept")
        ->assertOk()
        ->assertJsonPath('data.status', 'accepted');

    expect(BioSuggestion::query()->count())->toBe(1)
        ->and($profile->fresh()->bio)->toBe('Pratico tênis e busco novas partidas.');
    Bus::assertDispatched(GenerateProfileBioEmbedding::class, 1);
});

it('dispatches the embedding only after the accepting transaction commits', function () {
    Bus::fake();
    $profile = acceptanceSportProfile(805);
    $suggestion = generatedBioSuggestion($profile);

    DB::beginTransaction();
    app(BioSuggestionService::class)->acceptForUser(805, $suggestion->id);
    Bus::assertNotDispatched(GenerateProfileBioEmbedding::class);
    DB::rollBack();

    Bus::assertNotDispatched(GenerateProfileBioEmbedding::class);
});
