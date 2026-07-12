<?php

use App\Ai\Agents\BioAssistant;
use App\Enums\BioSuggestionStatus;
use App\Models\AiAuditEvent;
use App\Models\BioSuggestion;
use App\Models\Sport;
use App\Models\SportProfile;
use Illuminate\Cache\RateLimiter;

function bioSportProfile(int $userId, string $name = 'Perfil Bio'): SportProfile
{
    return SportProfile::query()->create([
        'user_id' => $userId,
        'display_name' => $name,
        'bio' => 'Bio original não deve ser alterada.',
        'city' => 'São Paulo',
        'latitude_approx' => -23.551,
        'longitude_approx' => -46.633,
    ]);
}

it('creates a private structured bio suggestion from safe sport context', function () {
    $profile = bioSportProfile(701);
    $sport = Sport::query()->create(['name' => 'Tênis', 'slug' => 'tenis']);
    $profile->sports()->create([
        'sport_id' => $sport->id,
        'level' => 'intermediate',
        'goals' => ['jogar', 'fazer-amigos'],
        'preferred_positions' => 'duplas',
        'is_primary' => true,
    ]);
    $profile->availabilityWindows()->create([
        'weekday' => 6,
        'starts_at' => '09:00',
        'ends_at' => '11:00',
    ]);

    BioAssistant::fake([[
        'bio' => 'Pratico tênis em nível intermediário e busco jogar em duplas e conhecer novas pessoas.',
        'key_points' => ['Tênis intermediário', 'Duplas', 'Fazer amizades'],
    ]])->preventStrayPrompts();

    $response = actingAsWorkspace(1, ['id' => 701])
        ->postJson('/api/profile/bio-suggestions', ['instruction' => 'Tom leve e direto.'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'generated')
        ->assertJsonPath('data.bio', 'Pratico tênis em nível intermediário e busco jogar em duplas e conhecer novas pessoas.')
        ->assertJsonMissingPath('data.provider')
        ->assertJsonMissingPath('data.model')
        ->assertJsonMissingPath('data.usage');

    expect($response->json('data'))->not->toHaveKey('context_fingerprint')
        ->and($profile->fresh()->bio)->toBe('Bio original não deve ser alterada.')
        ->and(BioSuggestion::query()->where('sport_profile_id', $profile->id)->count())->toBe(1);

    expect(AiAuditEvent::query()->sole()->metadata)->toMatchArray([
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'prompt_version' => 'bio_v1',
        'fallback_used' => false,
    ]);

    BioAssistant::assertPrompted(function ($prompt) {
        return $prompt->contains('Tênis')
            && $prompt->contains('intermediate')
            && $prompt->contains('Tom leve e direto.')
            && ! $prompt->contains('latitude_approx')
            && ! $prompt->contains('longitude_approx')
            && ! $prompt->contains('user_id')
            && ! $prompt->contains('Bio original');
    });
});

it('rejects generation when sport context and instruction are absent', function () {
    bioSportProfile(702);
    BioAssistant::fake()->preventStrayPrompts();

    actingAsWorkspace(1, ['id' => 702])
        ->postJson('/api/profile/bio-suggestions')
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    expect(BioSuggestion::query()->count())->toBe(0);
    BioAssistant::assertNeverPrompted();
});

it('lists only suggestions owned by the current sport profile', function () {
    $mine = bioSportProfile(703);
    $other = bioSportProfile(704, 'Outro perfil');

    BioSuggestion::query()->create([
        'sport_profile_id' => $mine->id,
        'status' => BioSuggestionStatus::Generated,
        'generated_bio' => 'Minha sugestão',
        'structured_output' => ['bio' => 'Minha sugestão', 'key_points' => []],
        'prompt_version' => 'bio_v1',
    ]);
    BioSuggestion::query()->create([
        'sport_profile_id' => $other->id,
        'status' => BioSuggestionStatus::Generated,
        'generated_bio' => 'Sugestão alheia',
        'structured_output' => ['bio' => 'Sugestão alheia', 'key_points' => []],
        'prompt_version' => 'bio_v1',
    ]);

    actingAsWorkspace(1, ['id' => 703])
        ->getJson('/api/profile/bio-suggestions')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.bio', 'Minha sugestão')
        ->assertJsonMissing(['bio' => 'Sugestão alheia']);
});

it('rejects unsafe structured output and records a private failure', function () {
    $profile = bioSportProfile(705);
    $sport = Sport::query()->create(['name' => 'Corrida', 'slug' => 'corrida']);
    $profile->sports()->create(['sport_id' => $sport->id, 'level' => 'beginner', 'goals' => ['treinar']]);

    BioAssistant::fake([[
        'bio' => 'Sou campeão certificado com CREF e 10 anos de experiência.',
        'key_points' => ['Credenciais'],
    ]]);

    actingAsWorkspace(1, ['id' => 705])
        ->postJson('/api/profile/bio-suggestions')
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    expect(BioSuggestion::query()->first()->status)->toBe(BioSuggestionStatus::Failed)
        ->and(BioSuggestion::query()->first()->failure_code)->toBe('unsafe_output')
        ->and(AiAuditEvent::query()->sole()->metadata)->toMatchArray(['failure_category' => 'unsafe_output']);
});

it('rejects private data in the instruction before calling the provider', function () {
    bioSportProfile(708);
    BioAssistant::fake()->preventStrayPrompts();

    actingAsWorkspace(1, ['id' => 708])
        ->postJson('/api/profile/bio-suggestions', [
            'instruction' => 'Inclua meu email pessoa@example.com e coordenadas -23.5505,-46.6333.',
        ])
        ->assertUnprocessable();

    expect(BioSuggestion::query()->count())->toBe(0);
    BioAssistant::assertNeverPrompted();
});

it('rejects a generated bio that introduces another known modality', function () {
    $profile = bioSportProfile(709);
    $tennis = Sport::query()->create(['name' => 'Tênis', 'slug' => 'tenis']);
    Sport::query()->create(['name' => 'Futebol', 'slug' => 'futebol']);
    $profile->sports()->create(['sport_id' => $tennis->id, 'level' => 'intermediate', 'goals' => ['jogar']]);

    BioAssistant::fake([[
        'bio' => 'Jogo tênis e futebol aos fins de semana.',
        'key_points' => ['Tênis', 'Futebol'],
    ]]);

    actingAsWorkspace(1, ['id' => 709])
        ->postJson('/api/profile/bio-suggestions')
        ->assertUnprocessable();

    expect(BioSuggestion::query()->first()->failure_code)->toBe('unsafe_output');
});

it('rejects unsupported experience teaching and uncatalogued sport claims', function () {
    $profile = bioSportProfile(710);
    $tennis = Sport::query()->create(['name' => 'Tênis', 'slug' => 'tenis']);
    $profile->sports()->create(['sport_id' => $tennis->id, 'level' => 'intermediate', 'goals' => ['jogar']]);

    BioAssistant::fake([[
        'bio' => 'Tenho experiência, dou aulas de tênis e pratico escalada.',
        'key_points' => ['Experiência'],
    ]]);

    actingAsWorkspace(1, ['id' => 710])
        ->postJson('/api/profile/bio-suggestions')
        ->assertUnprocessable();

    expect(BioSuggestion::query()->first()->failure_code)->toBe('unsafe_output');
});

it('enforces per-user generation rate limits', function () {
    config()->set('bio_assisted.rate_limit.max_attempts', 1);
    $userId = random_int(100000, 900000);
    app(RateLimiter::class)->clear("bio-suggestion:user:{$userId}");
    $profile = bioSportProfile($userId);
    $sport = Sport::query()->create(['name' => 'Yoga', 'slug' => 'yoga']);
    $profile->sports()->create(['sport_id' => $sport->id, 'level' => 'beginner', 'goals' => ['aprender']]);
    BioAssistant::fake([[
        'bio' => 'Pratico yoga e quero aprender com tranquilidade.',
        'key_points' => ['Yoga'],
    ]]);

    actingAsWorkspace(1, ['id' => $userId])->postJson('/api/profile/bio-suggestions')->assertCreated();
    actingAsWorkspace(1, ['id' => $userId])->postJson('/api/profile/bio-suggestions')->assertTooManyRequests();
    actingAsWorkspace(1, ['id' => $userId])->postJson('/api/profile/bio-suggestions')->assertTooManyRequests();

    expect(AiAuditEvent::query()->where('outcome', 'rate_limited')->count())->toBe(1)
        ->and(AiAuditEvent::query()->where('outcome', 'rate_limited')->sole()->metadata)->toMatchArray([
            'rate_limit_max_attempts' => 1,
            'rate_limit_decay_seconds' => 3600,
        ]);
});
