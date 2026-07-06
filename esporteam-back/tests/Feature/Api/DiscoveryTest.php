<?php

use App\Models\Connection;
use App\Models\Sport;
use App\Models\SportProfile;
use App\Models\TeacherProfile;

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

it('filters discovery by sport level and distance', function () {
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

    actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/discovery?sport_slug=tenis&level=intermediate&distance_km=5')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.profile.id', $nearMatch->id)
        ->assertJsonPath('data.0.reasons.0', 'same_sport')
        ->assertJsonPath('data.0.reasons.1', 'compatible_level');
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
        ->assertJsonPath('data.0.reasons.2', 'available');
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
        ->and($payload['profile']['location'])->toHaveKeys(['latitude_approx', 'longitude_approx'])
        ->and($payload['profile']['location'])->not->toHaveKeys(['latitude', 'longitude']);
});
