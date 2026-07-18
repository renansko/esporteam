<?php

use App\Models\Connection;
use App\Models\Sport;
use App\Models\SportProfile;
use App\Models\SportSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

it('returns not found instead of querying a textual session identifier as a bigint', function () {
    actingAsWorkspace(1, ['id' => 77])
        ->getJson('/api/sessions/sport-session-corrida-parque')
        ->assertNotFound();

    actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions/sport-session-corrida-parque/join')
        ->assertNotFound();
});

function createSessionSportProfileForUser(int $userId, string $name): SportProfile
{
    return SportProfile::query()->create([
        'user_id' => $userId,
        'display_name' => $name,
    ]);
}

it('creates a sport session for the authenticated sport profile', function () {
    $creator = createSessionSportProfileForUser(77, 'Creator');
    $sport = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);

    $sessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'creator_profile_id' => 999,
            'sport_id' => $sport->id,
            'title' => 'Treino de saque',
            'description' => 'Quadra reservada para treino.',
            'type' => 'treino',
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
            'location_label' => 'Parque municipal',
            'latitude_approx' => -23.55,
            'longitude_approx' => -46.63,
            'capacity' => 2,
            'visibility' => 'public',
        ])
        ->assertCreated()
        ->assertJsonPath('data.creator_profile_id', $creator->id)
        ->assertJsonPath('data.sport_id', $sport->id)
        ->assertJsonPath('data.type', 'treino')
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.participant_count', 1)
        ->json('data.id');

    expect(DB::table('session_participants')
        ->where('sport_session_id', $sessionId)
        ->where('sport_profile_id', $creator->id)
        ->where('status', 'joined')
        ->exists())->toBeTrue();
});

it('rejects paid fields when creating a sport session', function () {
    createSessionSportProfileForUser(77, 'Creator');

    actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'title' => 'Treino pago',
            'type' => 'treino',
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
            'price_cents' => 5000,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['price_cents']);
});

it('lists open public sport sessions by filters', function () {
    $sport = Sport::query()->create(['name' => 'Corrida', 'slug' => 'corrida']);
    $otherSport = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);
    createSessionSportProfileForUser(77, 'Current profile');

    $matchingId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'sport_id' => $sport->id,
            'title' => 'Corrida matinal',
            'type' => 'corrida',
            'starts_at' => now()->addDays(2)->setSecond(0)->toISOString(),
            'location_label' => 'Lago',
            'city' => 'Sao Paulo',
            'region' => 'SP',
            'capacity' => 10,
        ])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'sport_id' => $otherSport->id,
            'title' => 'Tenis fechado',
            'type' => 'treino',
            'starts_at' => now()->addDays(2)->setSecond(0)->toISOString(),
            'status' => 'cancelled',
            'capacity' => 10,
        ])
        ->assertCreated();

    actingAsWorkspace(1, ['id' => 77])
        ->getJson("/api/sessions?sport_id={$sport->id}&type=corrida&city=Sao%20Paulo")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingId)
        ->assertJsonPath('data.0.status', 'open')
        ->assertJsonPath('data.0.participant_count', 1);
});

it('lists only public sessions inside a requested map viewport', function () {
    $host = createSessionSportProfileForUser(77, 'Host');
    $sport = Sport::query()->create(['name' => 'Corrida', 'slug' => 'corrida']);

    $inside = SportSession::query()->create([
        'creator_profile_id' => $host->id,
        'sport_id' => $sport->id,
        'title' => 'Corrida no Ibirapuera',
        'type' => 'corrida',
        'starts_at' => now()->addDay(),
        'latitude_approx' => -23.586,
        'longitude_approx' => -46.657,
        'capacity' => 8,
        'visibility' => 'public',
        'status' => 'open',
    ]);
    SportSession::query()->create([
        'creator_profile_id' => $host->id,
        'sport_id' => $sport->id,
        'title' => 'Corrida longe',
        'type' => 'corrida',
        'starts_at' => now()->addDay(),
        'latitude_approx' => -23.800,
        'longitude_approx' => -46.700,
        'capacity' => 8,
        'visibility' => 'public',
        'status' => 'open',
    ]);

    actingAsWorkspace(1, ['id' => 88])
        ->getJson('/api/sessions?south=-23.60&north=-23.57&west=-46.68&east=-46.64')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inside->id);
});

it('lists public sessions by match filters without exposing vacancy counts', function () {
    $tennis = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);
    $current = createSessionSportProfileForUser(88, 'Candidate');
    $current->update([
        'latitude_approx' => -23.550,
        'longitude_approx' => -46.633,
    ]);
    $current->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);

    $host = createSessionSportProfileForUser(77, 'Host');
    $matchingId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'sport_id' => $tennis->id,
            'title' => 'Tenis publico',
            'type' => 'partida',
            'starts_at' => CarbonImmutable::parse('2026-07-07 19:30:00')->toISOString(),
            'latitude_approx' => -23.551,
            'longitude_approx' => -46.634,
            'capacity' => 4,
            'entry_mode' => 'publica_direta',
            'min_level' => 'beginner',
            'max_level' => 'intermediate',
        ])
        ->assertCreated()
        ->assertJsonPath('data.entry_mode', 'publica_direta')
        ->json('data.id');

    $fullId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'sport_id' => $tennis->id,
            'title' => 'Tenis lotado',
            'type' => 'partida',
            'starts_at' => CarbonImmutable::parse('2026-07-07 20:00:00')->toISOString(),
            'latitude_approx' => -23.551,
            'longitude_approx' => -46.634,
            'capacity' => 1,
            'entry_mode' => 'publica_direta',
            'min_level' => 'beginner',
            'max_level' => 'intermediate',
        ])
        ->assertCreated()
        ->json('data.id');

    SportProfile::query()->create(['user_id' => 99, 'display_name' => 'Far host']);
    actingAsWorkspace(1, ['id' => 99])
        ->postJson('/api/sessions', [
            'sport_id' => $tennis->id,
            'title' => 'Tenis longe',
            'type' => 'partida',
            'starts_at' => CarbonImmutable::parse('2026-07-07 19:30:00')->toISOString(),
            'latitude_approx' => -23.900,
            'longitude_approx' => -46.900,
            'capacity' => 4,
            'entry_mode' => 'publica_direta',
            'min_level' => 'beginner',
            'max_level' => 'intermediate',
        ])
        ->assertCreated();

    actingAsWorkspace(1, ['id' => 77])
        ->postJson("/api/sessions/{$fullId}/join")
        ->assertUnprocessable();

    $payload = actingAsWorkspace(1, ['id' => 88])
        ->getJson('/api/sessions?sport_slug=tenis&level=intermediate&distance_km=5&weekday=2&starts_at=19:00&ends_at=21:00&has_available_slots=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingId)
        ->assertJsonPath('data.0.next_action', 'entrar')
        ->assertJsonPath('data.0.participant_count', 1)
        ->json('data.0');

    expect($payload)->not->toHaveKeys(['capacity', 'available', 'available_slots']);
    expect($host->id)->toBeInt();
});

it('opens public session detail with requester participation and safe public fields', function () {
    $host = createSessionSportProfileForUser(77, 'Host');
    $participant = createSessionSportProfileForUser(88, 'Participant');

    $sessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'title' => 'Sessao no parque',
            'description' => 'Treino aberto.',
            'type' => 'treino',
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
            'location_label' => 'Parque municipal',
            'city' => 'Sao Paulo',
            'region' => 'SP',
            'latitude_approx' => -23.55,
            'longitude_approx' => -46.63,
            'capacity' => 3,
            'entry_mode' => 'publica_direta',
        ])
        ->assertCreated()
        ->json('data.id');

    $detail = actingAsWorkspace(1, ['id' => 88])
        ->getJson("/api/sessions/{$sessionId}")
        ->assertOk()
        ->assertJsonPath('data.id', $sessionId)
        ->assertJsonPath('data.creator.id', $host->id)
        ->assertJsonPath('data.location.latitude_approx', -23.55)
        ->assertJsonPath('data.next_action', 'entrar')
        ->json('data');

    expect($detail)->not->toHaveKey('capacity')
        ->and($detail['participation'])->toBe([]);

    actingAsWorkspace(1, ['id' => 88])
        ->postJson("/api/sessions/{$sessionId}/join")
        ->assertCreated()
        ->assertJsonPath('data.participant_count', 2);

    actingAsWorkspace(1, ['id' => 88])
        ->getJson("/api/sessions/{$sessionId}")
        ->assertOk()
        ->assertJsonPath('data.participation.0.profile.id', $participant->id)
        ->assertJsonPath('data.participation.0.status', 'joined');
});

it('hides private and blocked-host session detail', function () {
    $host = createSessionSportProfileForUser(77, 'Host');
    $viewer = createSessionSportProfileForUser(88, 'Viewer');

    $sessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'title' => 'Sessao privada',
            'type' => 'treino',
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
            'visibility' => 'private',
        ])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 88])
        ->getJson("/api/sessions/{$sessionId}")
        ->assertNotFound();

    Connection::query()->create([
        'requester_profile_id' => $host->id,
        'target_profile_id' => $viewer->id,
        'profile_low_id' => min($host->id, $viewer->id),
        'profile_high_id' => max($host->id, $viewer->id),
        'type' => 'block',
        'status' => 'blocked',
    ]);

    $blockedSessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'title' => 'Sessao bloqueada',
            'type' => 'treino',
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
        ])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 88])
        ->getJson("/api/sessions/{$blockedSessionId}")
        ->assertNotFound();
});

it('lets a sport profile join an open session once', function () {
    createSessionSportProfileForUser(77, 'Creator');
    $participant = createSessionSportProfileForUser(88, 'Participant');

    $sessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'title' => 'Pelada',
            'type' => 'partida',
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
            'capacity' => 3,
        ])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 88])
        ->postJson("/api/sessions/{$sessionId}/join")
        ->assertCreated()
        ->assertJsonPath('data.participant_count', 2)
        ->assertJsonPath('data.participants.1.id', $participant->id);

    actingAsWorkspace(1, ['id' => 88])
        ->postJson("/api/sessions/{$sessionId}/join")
        ->assertUnprocessable();
});

it('lists participant sessions with current profile status, including declined history', function () {
    createSessionSportProfileForUser(77, 'Host');
    $participant = createSessionSportProfileForUser(88, 'Participant');

    $sessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'title' => 'Sessao curada',
            'type' => 'partida',
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
            'entry_mode' => 'publica_aprovacao',
        ])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 88])
        ->postJson("/api/sessions/{$sessionId}/join")
        ->assertCreated()
        ->assertJsonPath('data.participation.1.status', 'interested');

    actingAsWorkspace(1, ['id' => 77])
        ->patchJson("/api/sessions/{$sessionId}/participants/{$participant->id}", ['action' => 'decline'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 88])
        ->getJson('/api/profile/sessions')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $sessionId)
        ->assertJsonPath('data.0.participation.0.profile.id', $participant->id)
        ->assertJsonPath('data.0.participation.0.status', 'declined');
});

it('returns every persisted participation state and empty history safely', function () {
    $host = createSessionSportProfileForUser(77, 'Host');
    $participant = createSessionSportProfileForUser(88, 'Participant');
    $statuses = ['joined', 'approved', 'interested', 'invited', 'declined', 'removed', 'left'];

    foreach ($statuses as $index => $status) {
        $session = SportSession::query()->create([
            'creator_profile_id' => $host->id,
            'title' => "Historico {$index}",
            'type' => 'partida',
            'starts_at' => now()->addDays($index + 1)->setSecond(0),
            'entry_mode' => 'publica_direta',
            'status' => 'open',
            'visibility' => 'public',
        ]);

        $session->participants()->attach($participant->id, ['status' => $status]);
    }

    $payload = actingAsWorkspace(1, ['id' => 88])
        ->getJson('/api/profile/sessions')
        ->assertOk()
        ->assertJsonCount(count($statuses), 'data')
        ->json('data');

    expect(collect($payload)->pluck('participation.0.status')->sort()->values()->all())
        ->toBe(collect($statuses)->sort()->values()->all());

    createSessionSportProfileForUser(89, 'Empty history');
    actingAsWorkspace(1, ['id' => 89])
        ->getJson('/api/profile/sessions')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    actingAsWorkspace(1, ['id' => 99])
        ->getJson('/api/profile/sessions')
        ->assertNotFound();
});

it('lets eligible profiles join public direct sessions without prior match', function () {
    $tennis = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);
    createSessionSportProfileForUser(77, 'Host');
    $participant = createSessionSportProfileForUser(88, 'Participant');
    $participant->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);

    $sessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'sport_id' => $tennis->id,
            'title' => 'Aberta sem match',
            'type' => 'partida',
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
            'capacity' => 3,
            'entry_mode' => 'publica_direta',
            'min_level' => 'beginner',
            'max_level' => 'advanced',
        ])
        ->assertCreated()
        ->assertJsonPath('data.entry_mode', 'publica_direta')
        ->assertJsonPath('data.requires_approval', false)
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 88])
        ->postJson("/api/sessions/{$sessionId}/join")
        ->assertCreated()
        ->assertJsonPath('data.participant_count', 2)
        ->assertJsonPath('data.participation.1.profile.id', $participant->id)
        ->assertJsonPath('data.participation.1.status', 'joined');
});

it('lets eligible profiles request approval while blocking unsafe or ineligible public entry', function () {
    $tennis = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);
    $host = createSessionSportProfileForUser(77, 'Host');
    $eligible = createSessionSportProfileForUser(88, 'Eligible');
    $eligible->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);
    $beginner = createSessionSportProfileForUser(99, 'Beginner');
    $beginner->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'beginner',
        'goals' => ['jogar'],
    ]);
    $blocked = createSessionSportProfileForUser(100, 'Blocked');
    $blocked->sports()->create([
        'sport_id' => $tennis->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);
    Connection::query()->create([
        'requester_profile_id' => $host->id,
        'target_profile_id' => $blocked->id,
        'profile_low_id' => min($host->id, $blocked->id),
        'profile_high_id' => max($host->id, $blocked->id),
        'type' => 'block',
        'status' => 'blocked',
    ]);

    $approvalSessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'sport_id' => $tennis->id,
            'title' => 'Aberta com aprovacao',
            'type' => 'partida',
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
            'capacity' => 2,
            'entry_mode' => 'publica_aprovacao',
            'min_level' => 'intermediate',
            'max_level' => 'advanced',
        ])
        ->assertCreated()
        ->assertJsonPath('data.entry_mode', 'publica_aprovacao')
        ->assertJsonPath('data.requires_approval', true)
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 88])
        ->postJson("/api/sessions/{$approvalSessionId}/join")
        ->assertCreated()
        ->assertJsonPath('data.participation.1.profile.id', $eligible->id)
        ->assertJsonPath('data.participation.1.status', 'interested');

    actingAsWorkspace(1, ['id' => 99])
        ->postJson("/api/sessions/{$approvalSessionId}/join")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['profile']);

    actingAsWorkspace(1, ['id' => 100])
        ->postJson("/api/sessions/{$approvalSessionId}/join")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['profile']);

    $inviteOnlySessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'sport_id' => $tennis->id,
            'title' => 'So convite',
            'type' => 'partida',
            'starts_at' => now()->addDays(2)->setSecond(0)->toISOString(),
            'capacity' => 2,
            'entry_mode' => 'convite',
        ])
        ->assertCreated()
        ->assertJsonPath('data.entry_mode', 'convite')
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 88])
        ->postJson("/api/sessions/{$inviteOnlySessionId}/join")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['entry_mode']);
});

it('rejects joins when session capacity or status does not allow entry', function () {
    createSessionSportProfileForUser(77, 'Creator');
    createSessionSportProfileForUser(88, 'First candidate');
    createSessionSportProfileForUser(99, 'Second candidate');

    $fullSessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'title' => 'Dupla fechada',
            'type' => 'partida',
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
            'capacity' => 1,
        ])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 88])
        ->postJson("/api/sessions/{$fullSessionId}/join")
        ->assertUnprocessable();

    $cancelledSessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'title' => 'Treino cancelado',
            'type' => 'treino',
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
            'status' => 'cancelled',
        ])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 99])
        ->postJson("/api/sessions/{$cancelledSessionId}/join")
        ->assertUnprocessable();
});

it('lets the host list compatible recommendations and invite profiles to a session', function () {
    $sport = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);
    $host = createSessionSportProfileForUser(77, 'Host');
    $host->sports()->create([
        'sport_id' => $sport->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);

    $recommended = createSessionSportProfileForUser(88, 'Recommended profile');
    $recommended->update([
        'city' => 'Sao Paulo',
        'region' => 'SP',
        'latitude_approx' => -23.551,
        'longitude_approx' => -46.634,
    ]);
    $recommended->sports()->create([
        'sport_id' => $sport->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);
    $recommended->availabilityWindows()->create([
        'weekday' => 2,
        'starts_at' => '18:00',
        'ends_at' => '21:00',
    ]);

    $wrongLevel = createSessionSportProfileForUser(99, 'Wrong level');
    $wrongLevel->sports()->create([
        'sport_id' => $sport->id,
        'level' => 'beginner',
        'goals' => ['jogar'],
    ]);
    $wrongLevel->availabilityWindows()->create([
        'weekday' => 2,
        'starts_at' => '18:00',
        'ends_at' => '21:00',
    ]);

    $hidden = createSessionSportProfileForUser(100, 'Hidden profile');
    $hidden->update(['visibility' => 'hidden']);
    $hidden->sports()->create([
        'sport_id' => $sport->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);

    $blocked = createSessionSportProfileForUser(101, 'Blocked profile');
    $blocked->sports()->create([
        'sport_id' => $sport->id,
        'level' => 'intermediate',
        'goals' => ['jogar'],
    ]);
    Connection::query()->create([
        'requester_profile_id' => $host->id,
        'target_profile_id' => $blocked->id,
        'profile_low_id' => min($host->id, $blocked->id),
        'profile_high_id' => max($host->id, $blocked->id),
        'type' => 'block',
        'status' => 'blocked',
    ]);

    $sessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'sport_id' => $sport->id,
            'title' => 'Tenis em grupo',
            'type' => 'partida',
            'starts_at' => CarbonImmutable::parse('2026-07-07 19:30:00')->toISOString(),
            'latitude_approx' => -23.550,
            'longitude_approx' => -46.633,
            'capacity' => 3,
        ])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 88])
        ->getJson("/api/sessions/{$sessionId}/recommendations")
        ->assertForbidden();

    $recommendationPayload = actingAsWorkspace(1, ['id' => 77])
        ->getJson("/api/sessions/{$sessionId}/recommendations?level=intermediate&goal=jogar&distance_km=5")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.profile.id', $recommended->id)
        ->assertJsonPath('data.0.reasons.0', 'same_sport')
        ->assertJsonPath('data.0.reasons.1', 'compatible_level')
        ->assertJsonPath('data.0.reasons.2', 'compatible_goal')
        ->assertJsonPath('data.0.reasons.3', 'available')
        ->json('data.0.profile');

    expect($recommendationPayload)->not->toHaveKey('location')
        ->and($recommendationPayload['safety_actions'])->toHaveKeys(['block', 'report']);

    actingAsWorkspace(1, ['id' => 77])
        ->postJson("/api/sessions/{$sessionId}/invites", [
            'profile_ids' => [$recommended->id],
            'price_cents' => 1000,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['price_cents']);

    actingAsWorkspace(1, ['id' => 77])
        ->postJson("/api/sessions/{$sessionId}/invites", [
            'profile_ids' => [$recommended->id],
        ])
        ->assertCreated()
        ->assertJsonPath('data.participation.1.profile.id', $recommended->id)
        ->assertJsonPath('data.participation.1.status', 'invited');

    $fullSessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'sport_id' => $sport->id,
            'title' => 'Tenis sem vagas',
            'type' => 'partida',
            'starts_at' => CarbonImmutable::parse('2026-07-08 19:30:00')->toISOString(),
            'capacity' => 1,
        ])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 77])
        ->postJson("/api/sessions/{$fullSessionId}/invites", [
            'profile_ids' => [$recommended->id],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['capacity']);
});

it('lets invited profiles accept or decline a hosted session invite', function () {
    $host = createSessionSportProfileForUser(77, 'Host');
    $accepted = createSessionSportProfileForUser(88, 'Accepted invitee');
    $declined = createSessionSportProfileForUser(99, 'Declined invitee');

    $sessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'title' => 'Pelada com convite',
            'type' => 'partida',
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
            'capacity' => 3,
        ])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 77])
        ->postJson("/api/sessions/{$sessionId}/invites", [
            'profile_ids' => [$accepted->id, $declined->id],
        ])
        ->assertCreated();

    actingAsWorkspace(1, ['id' => 88])
        ->patchJson("/api/sessions/{$sessionId}/invite", ['action' => 'accept'])
        ->assertOk()
        ->assertJsonPath('data.participant_count', 2)
        ->assertJsonPath('data.participation.1.profile.id', $accepted->id)
        ->assertJsonPath('data.participation.1.status', 'approved');

    actingAsWorkspace(1, ['id' => 99])
        ->patchJson("/api/sessions/{$sessionId}/invite", ['action' => 'decline'])
        ->assertOk()
        ->assertJsonPath('data.participant_count', 2)
        ->assertJsonPath('data.participation.2.profile.id', $declined->id)
        ->assertJsonPath('data.participation.2.status', 'declined');

    expect($host->id)->toBeInt();
});

it('lets hosts approve decline and remove requested seats while enforcing safety rules', function () {
    $host = createSessionSportProfileForUser(77, 'Host');
    $interested = createSessionSportProfileForUser(88, 'Interested profile');
    $second = createSessionSportProfileForUser(99, 'Second interested profile');
    $blocked = createSessionSportProfileForUser(100, 'Blocked interested profile');
    $hidden = createSessionSportProfileForUser(101, 'Hidden candidate');
    $hidden->update(['visibility' => 'hidden']);

    Connection::query()->create([
        'requester_profile_id' => $host->id,
        'target_profile_id' => $blocked->id,
        'profile_low_id' => min($host->id, $blocked->id),
        'profile_high_id' => max($host->id, $blocked->id),
        'type' => 'block',
        'status' => 'blocked',
    ]);

    $sessionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/sessions', [
            'title' => 'Dupla com aprovacao',
            'type' => 'partida',
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
            'capacity' => 2,
            'requires_approval' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.requires_approval', true)
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 88])
        ->postJson("/api/sessions/{$sessionId}/join")
        ->assertCreated()
        ->assertJsonPath('data.participant_count', 1)
        ->assertJsonPath('data.participation.1.profile.id', $interested->id)
        ->assertJsonPath('data.participation.1.status', 'interested');

    actingAsWorkspace(1, ['id' => 88])
        ->patchJson("/api/sessions/{$sessionId}/participants/{$interested->id}", ['action' => 'approve'])
        ->assertForbidden();

    actingAsWorkspace(1, ['id' => 77])
        ->patchJson("/api/sessions/{$sessionId}/participants/{$interested->id}", ['action' => 'approve'])
        ->assertOk()
        ->assertJsonPath('data.participant_count', 2)
        ->assertJsonPath('data.participation.1.status', 'approved');

    actingAsWorkspace(1, ['id' => 99])
        ->postJson("/api/sessions/{$sessionId}/join")
        ->assertCreated()
        ->assertJsonPath('data.participation.2.status', 'interested');

    actingAsWorkspace(1, ['id' => 77])
        ->patchJson("/api/sessions/{$sessionId}/participants/{$second->id}", ['action' => 'approve'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['capacity']);

    DB::table('session_participants')->insert([
        'sport_session_id' => $sessionId,
        'sport_profile_id' => $blocked->id,
        'status' => 'interested',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    actingAsWorkspace(1, ['id' => 77])
        ->patchJson("/api/sessions/{$sessionId}/participants/{$blocked->id}", ['action' => 'approve'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['profile']);

    actingAsWorkspace(1, ['id' => 77])
        ->postJson("/api/sessions/{$sessionId}/invites", ['profile_ids' => [$hidden->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['profile_ids']);

    actingAsWorkspace(1, ['id' => 77])
        ->patchJson("/api/sessions/{$sessionId}/participants/{$second->id}", ['action' => 'decline'])
        ->assertOk()
        ->assertJsonPath('data.participation.2.status', 'declined');

    actingAsWorkspace(1, ['id' => 77])
        ->patchJson("/api/sessions/{$sessionId}/participants/{$interested->id}", ['action' => 'remove'])
        ->assertOk()
        ->assertJsonPath('data.participant_count', 1)
        ->assertJsonPath('data.participation.1.status', 'removed');
});
