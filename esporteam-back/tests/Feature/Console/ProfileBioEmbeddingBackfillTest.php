<?php

use App\Jobs\GenerateProfileBioEmbedding;
use App\Models\ProfileBioEmbedding;
use App\Models\SportProfile;
use Illuminate\Support\Facades\Bus;

it('requeues profiles whose current bio has no valid embedding', function () {
    Bus::fake();
    $profile = SportProfile::query()->create([
        'user_id' => 904,
        'display_name' => 'Perfil backfill',
        'bio' => 'Procuro partidas de corrida.',
    ]);
    ProfileBioEmbedding::query()->create([
        'sport_profile_id' => $profile->id,
        'status' => 'failed',
        'source_hash' => hash('sha256', $profile->bio),
        'failure_code' => 'provider_unavailable',
    ]);

    $this->artisan('profile-bio-embeddings:backfill')->assertSuccessful();

    expect(ProfileBioEmbedding::query()->sole()->status)->toBe('pending');
    Bus::assertDispatched(GenerateProfileBioEmbedding::class);
});
