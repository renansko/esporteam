<?php

use App\Models\Connection;
use App\Models\Sport;
use App\Models\SportProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

function createPostMatchProfileForUser(int $userId, string $name): SportProfile
{
    return SportProfile::query()->create([
        'user_id' => $userId,
        'display_name' => $name,
    ]);
}

function createAcceptedPostMatchConnection(SportProfile $requester, SportProfile $target): Connection
{
    return Connection::query()->create([
        'requester_profile_id' => $requester->id,
        'target_profile_id' => $target->id,
        'profile_low_id' => min($requester->id, $target->id),
        'profile_high_id' => max($requester->id, $target->id),
        'type' => 'friendship',
        'status' => 'accepted',
    ]);
}

it('lists next actions for an accepted one-to-one match and creates a free session from it', function () {
    $tennis = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);
    $first = createPostMatchProfileForUser(77, 'Ana');
    $first->update([
        'city' => 'Sao Paulo',
        'region' => 'SP',
        'latitude_approx' => -23.550,
        'longitude_approx' => -46.633,
    ]);
    $first->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);
    $first->availabilityWindows()->create([
        'weekday' => 2,
        'starts_at' => '18:00',
        'ends_at' => '21:00',
    ]);

    $second = createPostMatchProfileForUser(88, 'Bruno');
    $second->update([
        'city' => 'Sao Paulo',
        'region' => 'SP',
        'latitude_approx' => -23.552,
        'longitude_approx' => -46.635,
    ]);
    $second->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);
    $second->availabilityWindows()->create([
        'weekday' => 2,
        'starts_at' => '19:00',
        'ends_at' => '20:00',
    ]);

    $connection = createAcceptedPostMatchConnection($first, $second);

    actingAsWorkspace(1, ['id' => 77])
        ->getJson("/api/post-match-actions?connection_id={$connection->id}")
        ->assertOk()
        ->assertJsonPath('data.context.type', 'connection')
        ->assertJsonPath('data.context.connection_id', $connection->id)
        ->assertJsonPath('data.next_actions.0.type', 'propor_horario')
        ->assertJsonPath('data.next_actions.0.available', true)
        ->assertJsonPath('data.next_actions.1.type', 'escolher_local')
        ->assertJsonPath('data.next_actions.1.available', true)
        ->assertJsonPath('data.next_actions.2.type', 'criar_sessao')
        ->assertJsonPath('data.time_suggestions.0.weekday', 2)
        ->assertJsonPath('data.time_suggestions.0.starts_at', '19:00')
        ->assertJsonPath('data.time_suggestions.0.ends_at', '20:00')
        ->assertJsonPath('data.reasons.0', 'same_sport')
        ->assertJsonPath('data.reasons.1', 'compatible_level')
        ->assertJsonPath('data.reasons.2', 'available');

    actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/post-match-actions/session', [
            'connection_id' => $connection->id,
            'starts_at' => CarbonImmutable::parse('2026-07-07 19:30:00')->toISOString(),
            'location_label' => 'Quadra 3',
            'city' => 'Sao Paulo',
            'price_cents' => 1500,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['price_cents']);

    $sessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/post-match-actions/session', [
            'connection_id' => $connection->id,
            'starts_at' => CarbonImmutable::parse('2026-07-07 19:30:00')->toISOString(),
            'location_label' => 'Quadra 3',
            'city' => 'Sao Paulo',
        ])
        ->assertCreated()
        ->assertJsonPath('data.creator_profile_id', $first->id)
        ->assertJsonPath('data.sport_id', $tennis->id)
        ->assertJsonPath('data.entry_mode', 'convite')
        ->assertJsonPath('data.visibility', 'private')
        ->assertJsonPath('data.participant_count', 2)
        ->json('data.id');

    expect(DB::table('session_participants')
        ->where('sport_session_id', $sessionId)
        ->where('sport_profile_id', $first->id)
        ->where('status', 'joined')
        ->exists())->toBeTrue()
        ->and(DB::table('session_participants')
            ->where('sport_session_id', $sessionId)
            ->where('sport_profile_id', $second->id)
            ->where('status', 'approved')
            ->exists())->toBeTrue();
});

it('lists group post-match actions and links the existing session to the chosen time and place', function () {
    $host = createPostMatchProfileForUser(77, 'Host');
    $accepted = createPostMatchProfileForUser(88, 'Accepted');

    foreach ([$host, $accepted] as $profile) {
        $profile->availabilityWindows()->create([
            'weekday' => 3,
            'starts_at' => '18:00',
            'ends_at' => '21:00',
        ]);
    }

    $sessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'title' => 'Grupo de corrida',
            'type' => 'corrida',
            'starts_at' => CarbonImmutable::parse('2026-07-08 18:00:00')->toISOString(),
            'capacity' => 3,
        ])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 77])
        ->postJson("/api/sessions/{$sessionId}/invites", [
            'profile_ids' => [$accepted->id],
        ])
        ->assertCreated();

    actingAsWorkspace(1, ['id' => 88])
        ->patchJson("/api/sessions/{$sessionId}/invite", ['action' => 'accept'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 88])
        ->getJson("/api/post-match-actions?session_id={$sessionId}")
        ->assertOk()
        ->assertJsonPath('data.context.type', 'session')
        ->assertJsonPath('data.next_actions.2.type', 'vincular_sessao')
        ->assertJsonPath('data.next_actions.2.available', true)
        ->assertJsonPath('data.next_actions.3.type', 'confirmar_presenca')
        ->assertJsonPath('data.next_actions.3.available', true)
        ->assertJsonPath('data.reasons.0', 'available')
        ->assertJsonPath('data.reasons.1', 'active_group');

    actingAsWorkspace(1, ['id' => 88])
        ->postJson('/api/post-match-actions/session', [
            'session_id' => $sessionId,
            'starts_at' => CarbonImmutable::parse('2026-07-08 19:00:00')->toISOString(),
            'location_label' => 'Pista central',
            'city' => 'Sao Paulo',
        ])
        ->assertCreated()
        ->assertJsonPath('data.id', $sessionId)
        ->assertJsonPath('data.location_label', 'Pista central')
        ->assertJsonPath('data.participant_count', 2);
});

it('rejects session post-match actions without an open accepted group match', function () {
    $host = createPostMatchProfileForUser(77, 'Host');
    $accepted = createPostMatchProfileForUser(88, 'Accepted');

    $soloSessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'title' => 'Sessao solo',
            'type' => 'treino',
            'starts_at' => CarbonImmutable::parse('2026-07-08 18:00:00')->toISOString(),
            'capacity' => 3,
        ])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 77])
        ->getJson("/api/post-match-actions?session_id={$soloSessionId}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['session_id']);

    $groupSessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'title' => 'Grupo cancelado',
            'type' => 'treino',
            'starts_at' => CarbonImmutable::parse('2026-07-08 19:00:00')->toISOString(),
            'capacity' => 3,
        ])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 77])
        ->postJson("/api/sessions/{$groupSessionId}/invites", [
            'profile_ids' => [$accepted->id],
        ])
        ->assertCreated();

    actingAsWorkspace(1, ['id' => 88])
        ->patchJson("/api/sessions/{$groupSessionId}/invite", ['action' => 'accept'])
        ->assertOk();

    DB::table('sport_sessions')
        ->where('id', $groupSessionId)
        ->update(['status' => 'cancelled']);

    actingAsWorkspace(1, ['id' => 88])
        ->getJson("/api/post-match-actions?session_id={$groupSessionId}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['session_id']);

    expect($host->id)->toBeInt();
});

it('returns no time proposal when accepted match profiles have no shared availability', function () {
    $first = createPostMatchProfileForUser(77, 'Ana');
    $first->availabilityWindows()->create([
        'weekday' => 1,
        'starts_at' => '08:00',
        'ends_at' => '09:00',
    ]);

    $second = createPostMatchProfileForUser(88, 'Bruno');
    $second->availabilityWindows()->create([
        'weekday' => 2,
        'starts_at' => '19:00',
        'ends_at' => '20:00',
    ]);

    $connection = createAcceptedPostMatchConnection($first, $second);

    actingAsWorkspace(1, ['id' => 77])
        ->getJson("/api/post-match-actions?connection_id={$connection->id}")
        ->assertOk()
        ->assertJsonCount(0, 'data.time_suggestions')
        ->assertJsonPath('data.next_actions.0.type', 'propor_horario')
        ->assertJsonPath('data.next_actions.0.available', false)
        ->assertJsonPath('data.next_actions.0.reason', 'no_shared_availability');
});
