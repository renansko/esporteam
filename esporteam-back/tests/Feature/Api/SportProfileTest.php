<?php

use App\Models\Sport;
use App\Models\SportProfile;
use Database\Seeders\DemoSeeder;

it('seeds the initial active sports idempotently', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Sport::query()->count())->toBe(12)
        ->and(Sport::query()->where('slug', 'futebol')->where('is_active', true)->exists())->toBeTrue()
        ->and(Sport::query()->where('slug', 'beach-tennis')->where('is_active', true)->exists())->toBeTrue();
});

it('returns an empty sport profile state before onboarding', function () {
    actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/profile')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Sport profile not created.',
        ])
        ->assertJsonMissingPath('data');
});

it('creates and returns the authenticated users sport profile', function () {
    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile', [
            'display_name' => 'Renan',
            'bio' => 'Jogo tenis e corro de manha.',
            'city' => 'Sao Paulo',
            'region' => 'SP',
            'latitude_approx' => -23.5505,
            'longitude_approx' => -46.6333,
            'visibility' => 'public',
        ])
        ->assertOk()
        ->assertJsonPath('data.user_id', 77)
        ->assertJsonPath('data.display_name', 'Renan')
        ->assertJsonPath('data.location.latitude_approx', -23.551)
        ->assertJsonPath('data.location.longitude_approx', -46.633)
        ->assertJsonPath('data.bio_assistant_onboarding.eligible', false)
        ->assertJsonPath('data.bio_assistant_onboarding.blocking_fields', []);

    $profile = SportProfile::query()->where('user_id', 77)->first();

    expect($profile)->not->toBeNull()
        ->and($profile->latitude_approx)->toBe(-23.551)
        ->and($profile->longitude_approx)->toBe(-46.633);
});

it('returns persistent assisted-bio onboarding eligibility and missing sport context', function () {
    actingAsWorkspace(1, ['id' => 91])
        ->putJson('/api/profile', ['display_name' => 'Perfil sem bio'])
        ->assertOk()
        ->assertJsonPath('data.bio_assistant_onboarding.eligible', true)
        ->assertJsonPath('data.bio_assistant_onboarding.completed_at', null)
        ->assertJsonPath('data.bio_assistant_onboarding.blocking_fields.0', 'sports');

    actingAsWorkspace(1, ['id' => 91])
        ->putJson('/api/profile', ['display_name' => 'Perfil com bio', 'bio' => 'Quero jogar tênis.'])
        ->assertOk()
        ->assertJsonPath('data.bio_assistant_onboarding.eligible', false)
        ->assertJsonPath('data.bio_assistant_onboarding.blocking_fields', [])
        ->assertJsonStructure(['data' => ['bio_assistant_onboarding' => ['completed_at']]]);

    expect(SportProfile::query()->where('user_id', 91)->value('bio_assistant_onboarding_completed_at'))->not->toBeNull();
});

it('updates the authenticated users existing sport profile', function () {
    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile', [
            'display_name' => 'Renan',
            'city' => 'Sao Paulo',
        ])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile', [
            'display_name' => 'Renan Silva',
            'bio' => 'Tenis aos fins de semana.',
            'city' => 'Campinas',
            'region' => 'SP',
            'visibility' => 'hidden',
        ])
        ->assertOk()
        ->assertJsonPath('data.user_id', 77)
        ->assertJsonPath('data.display_name', 'Renan Silva')
        ->assertJsonPath('data.city', 'Campinas')
        ->assertJsonPath('data.visibility', 'hidden');

    expect(SportProfile::query()->where('user_id', 77)->count())->toBe(1);
});

it('isolates sport profiles by authenticated user id', function () {
    SportProfile::query()->create([
        'user_id' => 88,
        'display_name' => 'Other profile',
        'city' => 'Curitiba',
    ]);

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile', [
            'display_name' => 'Current user',
            'city' => 'Sao Paulo',
        ])
        ->assertOk()
        ->assertJsonPath('data.user_id', 77)
        ->assertJsonPath('data.display_name', 'Current user');

    actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/profile')
        ->assertOk()
        ->assertJsonPath('data.user_id', 77)
        ->assertJsonPath('data.display_name', 'Current user');

    expect(SportProfile::query()->where('user_id', 88)->value('display_name'))->toBe('Other profile');
});

it('does not expose precise coordinate fields in the sport profile resource', function () {
    $payload = actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile', [
            'display_name' => 'Renan',
            'latitude_approx' => -23.5505,
            'longitude_approx' => -46.6333,
        ])
        ->assertOk()
        ->json('data');

    expect($payload)->not->toHaveKeys(['latitude', 'longitude'])
        ->and($payload['location'])->toHaveKeys(['latitude_approx', 'longitude_approx'])
        ->and($payload['location'])->not->toHaveKeys(['latitude', 'longitude'])
        ->and($payload['location']['latitude_approx'])->toBe(-23.551)
        ->and($payload['location']['longitude_approx'])->toBe(-46.633);
});

it('lists active sports ordered by name', function () {
    Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis', 'category' => 'raquete']);
    Sport::query()->create(['name' => 'Corrida', 'slug' => 'corrida', 'category' => 'endurance']);
    Sport::query()->create(['name' => 'Inativo', 'slug' => 'inativo', 'is_active' => false]);

    actingAsWorkspace(1)
        ->getJson('/api/sports')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Corrida')
        ->assertJsonPath('data.1.name', 'Tenis')
        ->assertJsonCount(2, 'data');
});

it('replaces sport preferences for the authenticated profile', function () {
    $sport = Sport::query()->create(['name' => 'Futebol', 'slug' => 'futebol']);

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile', ['display_name' => 'Renan'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile/sports', [
            'sports' => [[
                'sport_id' => $sport->id,
                'level' => 'intermediate',
                'goals' => ['jogar', 'fazer-amigos'],
                'preferred_positions' => 'meia',
                'is_primary' => true,
            ]],
        ])
        ->assertOk()
        ->assertJsonPath('data.sports.0.sport.slug', 'futebol')
        ->assertJsonPath('data.sports.0.level', 'intermediate')
        ->assertJsonPath('data.sports.0.goals.0', 'jogar')
        ->assertJsonPath('data.sports.0.goals.1', 'fazer-amigos')
        ->assertJsonPath('data.sports.0.is_primary', true);
});

it('allows saving an empty set of sport preferences', function () {
    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile', ['display_name' => 'Renan'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile/sports', ['sports' => []])
        ->assertOk()
        ->assertJsonCount(0, 'data.sports');
});

it('rejects sport preferences with inactive sports invalid levels or invalid goals', function () {
    $inactiveSport = Sport::query()->create([
        'name' => 'Inativo',
        'slug' => 'inativo',
        'is_active' => false,
    ]);

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile', ['display_name' => 'Renan'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile/sports', [
            'sports' => [[
                'sport_id' => $inactiveSport->id,
                'level' => 'professional',
                'goals' => ['networking'],
            ]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'sports.0.sport_id',
            'sports.0.level',
            'sports.0.goals.0',
        ]);
});

it('substitutes sport preferences instead of appending them', function () {
    $tennis = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);
    $running = Sport::query()->create(['name' => 'Corrida', 'slug' => 'corrida']);

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile', ['display_name' => 'Renan'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile/sports', [
            'sports' => [[
                'sport_id' => $tennis->id,
                'level' => 'beginner',
                'goals' => ['aprender'],
            ]],
        ])
        ->assertOk()
        ->assertJsonPath('data.sports.0.sport.slug', 'tenis');

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile/sports', [
            'sports' => [[
                'sport_id' => $running->id,
                'level' => 'competitive',
                'goals' => ['competir', 'treinar'],
                'is_primary' => true,
            ]],
        ])
        ->assertOk()
        ->assertJsonCount(1, 'data.sports')
        ->assertJsonPath('data.sports.0.sport.slug', 'corrida')
        ->assertJsonPath('data.sports.0.level', 'competitive')
        ->assertJsonPath('data.sports.0.goals.0', 'competir')
        ->assertJsonPath('data.sports.0.goals.1', 'treinar');

    expect($tennis->profileSports()->exists())->toBeFalse()
        ->and($running->profileSports()->exists())->toBeTrue();
});

it('rejects more than one primary sport practice', function () {
    $tennis = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);
    $running = Sport::query()->create(['name' => 'Corrida', 'slug' => 'corrida']);

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile', ['display_name' => 'Renan'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile/sports', [
            'sports' => [
                [
                    'sport_id' => $tennis->id,
                    'level' => 'beginner',
                    'goals' => ['aprender'],
                    'is_primary' => true,
                ],
                [
                    'sport_id' => $running->id,
                    'level' => 'intermediate',
                    'goals' => ['treinar'],
                    'is_primary' => true,
                ],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sports']);
});

it('replaces availability windows for the authenticated profile', function () {
    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile', ['display_name' => 'Renan'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile/availability', [
            'windows' => [[
                'weekday' => 2,
                'starts_at' => '19:00',
                'ends_at' => '21:00',
            ]],
        ])
        ->assertOk()
        ->assertJsonPath('data.availability.0.weekday', 2)
        ->assertJsonPath('data.availability.0.starts_at', '19:00')
        ->assertJsonPath('data.availability.0.ends_at', '21:00');

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile/availability', [
            'windows' => [[
                'weekday' => 4,
                'starts_at' => '07:30',
                'ends_at' => '08:30',
            ]],
        ])
        ->assertOk()
        ->assertJsonCount(1, 'data.availability')
        ->assertJsonPath('data.availability.0.weekday', 4)
        ->assertJsonPath('data.availability.0.starts_at', '07:30')
        ->assertJsonPath('data.availability.0.ends_at', '08:30');

    expect(SportProfile::query()->where('user_id', 77)->first()->availabilityWindows)->toHaveCount(1);
});

it('rejects invalid availability weekdays and time ranges', function () {
    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile', ['display_name' => 'Renan'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/profile/availability', [
            'windows' => [[
                'weekday' => 7,
                'starts_at' => '21:00',
                'ends_at' => '20:00',
            ]],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'windows.0.weekday',
            'windows.0.ends_at',
        ]);
});
