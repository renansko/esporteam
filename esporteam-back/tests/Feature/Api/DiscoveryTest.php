<?php

use App\Models\Connection;
use App\Models\Sport;
use App\Models\SportProfile;
use App\Models\SportSession;
use App\Models\TeacherProfile;
use Carbon\CarbonImmutable;

it('filters discovered sport profiles by overlapping availability windows', function () {
    SportProfile::query()->create([
        'user_id' => 77,
        'display_name' => 'Current profile',
    ]);

    $overlapping = SportProfile::query()->create([
        'user_id' => 88,
        'display_name' => 'Overlapping profile',
    ]);
    $overlapping->availabilityWindows()->create([
        'weekday' => 2,
        'starts_at' => '18:00',
        'ends_at' => '20:00',
    ]);

    $touchingButNotOverlapping = SportProfile::query()->create([
        'user_id' => 99,
        'display_name' => 'Later profile',
    ]);
    $touchingButNotOverlapping->availabilityWindows()->create([
        'weekday' => 2,
        'starts_at' => '20:00',
        'ends_at' => '21:00',
    ]);

    $differentWeekday = SportProfile::query()->create([
        'user_id' => 100,
        'display_name' => 'Different weekday profile',
    ]);
    $differentWeekday->availabilityWindows()->create([
        'weekday' => 3,
        'starts_at' => '18:00',
        'ends_at' => '20:00',
    ]);

    actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/discovery?weekday=2&starts_at=19:00&ends_at=20:00')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'person')
        ->assertJsonPath('data.0.profile.id', $overlapping->id)
        ->assertJsonPath('data.0.profile.availability.0.weekday', 2)
        ->assertJsonPath('data.0.reasons.0', 'available');
});

it('filters discovery by sport and level while distance only ranks results', function () {
    $tennis = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);
    Sport::query()->create(['name' => 'Corrida', 'slug' => 'corrida']);

    $current = SportProfile::query()->create([
        'user_id' => 77,
        'display_name' => 'Current profile',
        'latitude_approx' => -23.550,
        'longitude_approx' => -46.633,
    ]);
    $current->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);

    $nearMatch = SportProfile::query()->create([
        'user_id' => 88,
        'display_name' => 'Near match',
        'latitude_approx' => -23.560,
        'longitude_approx' => -46.640,
    ]);
    $nearMatch->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);

    $wrongLevel = SportProfile::query()->create([
        'user_id' => 99,
        'display_name' => 'Wrong level',
        'latitude_approx' => -23.561,
        'longitude_approx' => -46.641,
    ]);
    $wrongLevel->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'beginner',
        'goals' => ['aprender'],
    ]);

    $farMatch = SportProfile::query()->create([
        'user_id' => 100,
        'display_name' => 'Far match',
        'latitude_approx' => -23.900,
        'longitude_approx' => -46.900,
    ]);
    $farMatch->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);
    TeacherProfile::query()->create([
        'sport_profile_id' => $farMatch->id,
        'headline' => 'Professor de tenis',
    ]);

    actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/discovery?sport_slug=tenis&level=intermediate&distance_km=5')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.profile.id', $nearMatch->id)
        ->assertJsonPath('data.1.profile.id', $farMatch->id)
        ->assertJsonPath('data.1.type', 'teacher')
        ->assertJsonPath('data.0.reasons.0', 'same_sport')
        ->assertJsonPath('data.0.reasons.1', 'compatible_level');
});

it('keeps public sessions discoverable with a distance preference when the viewer has no profile', function () {
    $host = SportProfile::query()->create([
        'user_id' => 88,
        'display_name' => 'Session host',
        'latitude_approx' => -23.900,
        'longitude_approx' => -46.900,
    ]);
    $session = SportSession::query()->create([
        'creator_profile_id' => $host->id,
        'title' => 'Sessao publica distante',
        'type' => 'partida',
        'starts_at' => now()->addDay(),
        'visibility' => 'public',
        'status' => 'open',
    ]);

    actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/discovery?mode=sessions&distance_km=10')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.session.id', $session->id);
});

it('ranks discovery by deterministic match signals', function () {
    $tennis = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);
    $running = Sport::query()->create(['name' => 'Corrida', 'slug' => 'corrida']);

    $current = SportProfile::query()->create([
        'user_id' => 77,
        'display_name' => 'Current profile',
        'latitude_approx' => -23.550,
        'longitude_approx' => -46.633,
    ]);
    $current->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);
    $current->availabilityWindows()->create([
        'weekday' => 2,
        'starts_at' => '19:00',
        'ends_at' => '21:00',
    ]);

    $best = SportProfile::query()->create([
        'user_id' => 88,
        'display_name' => 'Best match',
        'bio' => 'Jogo toda semana.',
        'city' => 'Sao Paulo',
        'region' => 'SP',
        'avatar_url' => 'https://example.com/avatar.jpg',
        'latitude_approx' => -23.551,
        'longitude_approx' => -46.634,
    ]);
    $best->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);
    $best->availabilityWindows()->create([
        'weekday' => 2,
        'starts_at' => '18:00',
        'ends_at' => '20:00',
    ]);

    $weaker = SportProfile::query()->create([
        'user_id' => 99,
        'display_name' => 'Weaker match',
        'latitude_approx' => -23.552,
        'longitude_approx' => -46.635,
    ]);
    $weaker->sports()->create([
        'sport_id' => $running->id,
        'level' => 'beginner',
        'goals' => ['aprender'],
    ]);

    actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/discovery')
        ->assertOk()
        ->assertJsonPath('data.0.profile.id', $best->id)
        ->assertJsonPath('data.0.reasons.0', 'same_sport')
        ->assertJsonPath('data.0.reasons.1', 'compatible_level')
        ->assertJsonPath('data.0.reasons.2', 'available')
        ->assertJsonPath('data.0.primary_sport.sport.slug', 'tenis')
        ->assertJsonPath('data.0.primary_sport.level', 'intermediate')
        ->assertJsonPath('data.0.availability_summary.window_count', 1)
        ->assertJsonPath('data.0.location_label', 'Sao Paulo, SP')
        ->assertJsonPath('data.0.recommendation_reason', 'same_sport');
});

it('excludes hidden self and blocked profiles from discovery', function () {
    $current = SportProfile::query()->create([
        'user_id' => 77,
        'display_name' => 'Current profile',
    ]);

    $visible = SportProfile::query()->create([
        'user_id' => 88,
        'display_name' => 'Visible profile',
    ]);

    SportProfile::query()->create([
        'user_id' => 99,
        'display_name' => 'Hidden profile',
        'visibility' => 'hidden',
    ]);

    $blocked = SportProfile::query()->create([
        'user_id' => 100,
        'display_name' => 'Blocked profile',
    ]);

    Connection::query()->create([
        'requester_profile_id' => $current->id,
        'target_profile_id' => $blocked->id,
        'profile_low_id' => min($current->id, $blocked->id),
        'profile_high_id' => max($current->id, $blocked->id),
        'type' => 'block',
        'status' => 'blocked',
    ]);

    actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/discovery')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.profile.id', $visible->id);
});

it('differentiates teacher cards and hides precise coordinate fields', function () {
    SportProfile::query()->create([
        'user_id' => 77,
        'display_name' => 'Current profile',
        'latitude_approx' => -23.550,
        'longitude_approx' => -46.633,
    ]);

    $teacherProfile = SportProfile::query()->create([
        'user_id' => 88,
        'display_name' => 'Teacher profile',
        'latitude_approx' => -23.551,
        'longitude_approx' => -46.634,
    ]);

    TeacherProfile::query()->create([
        'sport_profile_id' => $teacherProfile->id,
        'headline' => 'Professora de tenis',
    ]);

    $payload = actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/discovery')
        ->assertOk()
        ->assertJsonPath('data.0.type', 'teacher')
        ->assertJsonPath('data.0.teacher_profile.headline', 'Professora de tenis')
        ->json('data.0');

    expect($payload['profile'])->not->toHaveKeys(['latitude', 'longitude'])
        ->and($payload['profile'])->not->toHaveKey('location')
        ->and($payload['trust_signals'])->toHaveKey('profile_complete')
        ->and($payload['safety_actions'])->toHaveKeys(['block', 'report'])
        ->and($payload['profile']['safety_actions'])->toHaveKeys(['block', 'report']);
});

it('returns typed session discovery cards with trust safety and approved participant signals', function () {
    $tennis = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);
    $running = Sport::query()->create(['name' => 'Corrida', 'slug' => 'corrida']);

    $current = SportProfile::query()->create([
        'user_id' => 77,
        'display_name' => 'Current profile',
        'latitude_approx' => -23.550,
        'longitude_approx' => -46.633,
    ]);
    $current->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);

    $host = SportProfile::query()->create([
        'user_id' => 88,
        'display_name' => 'Session host',
        'latitude_approx' => -23.552,
        'longitude_approx' => -46.635,
    ]);
    $host->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);

    $matching = SportSession::query()->create([
        'creator_profile_id' => $host->id,
        'sport_id' => $tennis->id,
        'title' => 'Tenis em duplas',
        'type' => 'partida',
        'starts_at' => CarbonImmutable::parse('2026-07-07 19:30:00'),
        'location_label' => 'Quadra Pinheiros',
        'city' => 'Sao Paulo',
        'region' => 'SP',
        'latitude_approx' => -23.551,
        'longitude_approx' => -46.634,
        'capacity' => 4,
        'requires_approval' => true,
        'visibility' => 'public',
        'status' => 'open',
    ]);
    $matching->participants()->attach($host->id, ['status' => 'joined']);

    $fullHost = SportProfile::query()->create([
        'user_id' => 98,
        'display_name' => 'Full session host',
        'latitude_approx' => -23.552,
        'longitude_approx' => -46.635,
    ]);
    $fullHost->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);
    $full = SportSession::query()->create([
        'creator_profile_id' => $fullHost->id,
        'sport_id' => $tennis->id,
        'title' => 'Tenis ja lotado',
        'type' => 'partida',
        'starts_at' => CarbonImmutable::parse('2026-07-07 20:00:00'),
        'location_label' => 'Quadra Pinheiros',
        'city' => 'Sao Paulo',
        'region' => 'SP',
        'latitude_approx' => -23.551,
        'longitude_approx' => -46.634,
        'capacity' => 1,
        'visibility' => 'public',
        'status' => 'open',
    ]);
    $full->participants()->attach($fullHost->id, ['status' => 'joined']);

    $wrongSport = SportProfile::query()->create(['user_id' => 99, 'display_name' => 'Runner host']);
    $wrongSport->sports()->create([
        'sport_id' => $running->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);
    SportSession::query()->create([
        'creator_profile_id' => $wrongSport->id,
        'sport_id' => $running->id,
        'title' => 'Corrida noturna',
        'type' => 'corrida',
        'starts_at' => CarbonImmutable::parse('2026-07-07 19:30:00'),
        'capacity' => 10,
        'visibility' => 'public',
        'status' => 'open',
    ]);

    $payload = actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/discovery?mode=sessions&type=partida&sport_slug=tenis&level=intermediate&goal=jogar&distance_km=5&weekday=2&starts_at=19:00&ends_at=21:00')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('mode', 'sessions')
        ->assertJsonPath('data.0.type', 'session')
        ->assertJsonPath('data.0.session.id', $matching->id)
        ->assertJsonPath('data.0.session.sport.slug', 'tenis')
        ->assertJsonPath('data.0.host.display_name', 'Session host')
        ->assertJsonPath('data.0.entry_rule', 'approval_required')
        ->assertJsonMissingPath('data.0.vacancy_status')
        ->assertJsonPath('data.0.participant_count', 1)
        ->assertJsonPath('data.0.session.participant_count', 1)
        ->assertJsonPath('data.0.session.requires_approval', true)
        ->assertJsonPath('data.0.session.location_label_public', 'Quadra Pinheiros')
        ->assertJsonPath('data.0.session.approved_participants.0.id', $host->id)
        ->json('data.0');

    expect($payload)->not->toHaveKeys(['price', 'price_cents', 'payment_url', 'slots', 'capacity', 'available', 'vacancy_status'])
        ->and($payload['session'])->not->toHaveKeys(['price', 'price_cents', 'payment_url', 'slots', 'capacity', 'available'])
        ->and($payload['session'])->not->toHaveKey('location')
        ->and($payload['host'])->not->toHaveKey('location')
        ->and($payload['safety_actions'])->toHaveKeys(['block', 'report']);
});

it('returns place discovery cards from open public sessions', function () {
    $tennis = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);
    $current = SportProfile::query()->create([
        'user_id' => 77,
        'display_name' => 'Current profile',
        'latitude_approx' => -23.550,
        'longitude_approx' => -46.633,
    ]);
    $current->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);

    $host = SportProfile::query()->create([
        'user_id' => 88,
        'display_name' => 'Session host',
        'latitude_approx' => -23.552,
        'longitude_approx' => -46.635,
    ]);
    $host->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);

    SportSession::query()->create([
        'creator_profile_id' => $host->id,
        'sport_id' => $tennis->id,
        'title' => 'Tenis em duplas',
        'type' => 'partida',
        'starts_at' => CarbonImmutable::parse('2026-07-07 19:30:00'),
        'location_label' => 'Quadra Pinheiros',
        'city' => 'Sao Paulo',
        'region' => 'SP',
        'latitude_approx' => -23.551,
        'longitude_approx' => -46.634,
        'capacity' => 4,
        'visibility' => 'public',
        'status' => 'open',
    ]);

    $payload = actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/discovery?mode=places&sport_slug=tenis&distance_km=5')
        ->assertOk()
        ->assertJsonPath('mode', 'places')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'place')
        ->assertJsonPath('data.0.place.label', 'Quadra Pinheiros')
        ->assertJsonPath('data.0.place.city', 'Sao Paulo')
        ->assertJsonPath('data.0.place.sports.0.slug', 'tenis')
        ->assertJsonPath('data.0.place.open_session_count', 1)
        ->json('data.0.place');

    expect($payload)->not->toHaveKey('location');
});

it('returns actionable empty state suggestions for discovery modes', function () {
    $tennis = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);

    $current = SportProfile::query()->create([
        'user_id' => 77,
        'display_name' => 'Current profile',
        'latitude_approx' => -23.550,
        'longitude_approx' => -46.633,
    ]);
    $current->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'beginner',
        'goals' => ['aprender'],
    ]);

    actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/discovery?mode=people&sport_slug=tenis&level=advanced&goal=competir&distance_km=1')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('mode', 'people')
        ->assertJsonPath('empty_state.suggestions.0.action', 'remove_level_filter')
        ->assertJsonPath('empty_state.suggestions.1.action', 'create_public_session');
});
