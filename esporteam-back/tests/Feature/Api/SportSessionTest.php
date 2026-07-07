<?php

use App\Models\Sport;
use App\Models\SportProfile;
use Illuminate\Support\Facades\DB;

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
