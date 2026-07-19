<?php

use App\Models\Sport;
use App\Models\SportProfile;
use App\Models\SportSession;
use App\Models\SportSessionSeries;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

function managedSeries(SportProfile $host, Sport $sport): SportSessionSeries
{
    return SportSessionSeries::query()->create([
        'creator_profile_id' => $host->id, 'sport_id' => $sport->id, 'title' => 'Volei de quarta', 'type' => 'partida',
        'starts_on' => '2026-10-21', 'starts_at_local' => '19:00', 'duration_minutes' => 90, 'timezone' => 'America/Sao_Paulo',
        'interval_weeks' => 1, 'weekdays' => [3], 'ends_type' => 'never', 'location_label_public' => 'Parque',
        'meeting_point_label' => 'Portao 2', 'city' => 'Sao Paulo', 'region' => 'SP', 'latitude_approx' => -23.500,
        'longitude_approx' => -46.600, 'latitude_exact' => -23.5001, 'longitude_exact' => -46.6001,
        'entry_mode' => 'publica_direta', 'visibility' => 'public', 'status' => 'active', 'publication_key' => 'managed-'.$host->id,
    ]);
}

function managedOccurrence(SportSessionSeries $series, string $key, string $startsAt): SportSession
{
    return SportSession::query()->create([
        'sport_session_series_id' => $series->id, 'creator_profile_id' => $series->creator_profile_id, 'sport_id' => $series->sport_id,
        'title' => $series->title, 'type' => 'partida', 'starts_at' => $startsAt, 'ends_at' => CarbonImmutable::parse($startsAt)->addMinutes(90),
        'timezone' => $series->timezone, 'location_label' => 'Parque', 'location_label_public' => 'Parque', 'meeting_point_label' => 'Portao 2',
        'city' => 'Sao Paulo', 'region' => 'SP', 'latitude_approx' => -23.500, 'longitude_approx' => -46.600,
        'latitude_exact' => -23.5001, 'longitude_exact' => -46.6001, 'entry_mode' => 'publica_direta', 'visibility' => 'public',
        'status' => 'open', 'occurrence_key' => $key,
    ]);
}

beforeEach(function () {
    config()->set('features.recurring_events', true);
});

it('keeps a one-occurrence edit as a durable override and rejects non-host access without disclosure', function () {
    $host = SportProfile::query()->create(['user_id' => 2801, 'display_name' => 'Lia', 'city' => 'Sao Paulo', 'region' => 'SP']);
    $other = SportProfile::query()->create(['user_id' => 2802, 'display_name' => 'Rui', 'city' => 'Sao Paulo', 'region' => 'SP']);
    $sport = Sport::query()->create(['name' => 'Volei', 'slug' => 'volei']);
    $series = managedSeries($host, $sport);
    $first = managedOccurrence($series, '2026-10-21T19:00:00-03:00', '2026-10-21 22:00:00+00:00');
    $sibling = managedOccurrence($series, '2026-10-28T19:00:00-03:00', '2026-10-28 22:00:00+00:00');

    actingAsWorkspace(1, ['id' => $other->user_id, 'is_adult' => true])
        ->patchJson("/api/sessions/{$first->id}/occurrence", ['version' => 1, 'title' => 'Nao pode'])
        ->assertNotFound();

    actingAsWorkspace(1, ['id' => $host->user_id, 'is_adult' => true])
        ->patchJson("/api/sessions/{$first->id}/occurrence", ['version' => 1, 'title' => 'Volei no ginasio'])
        ->assertOk()->assertJsonPath('data.title', 'Volei no ginasio')->assertJsonPath('data.is_series_override', true)
        ->assertJsonPath('data.change_notice', 'updated')->assertJsonPath('data.version', 2);

    expect($sibling->fresh()->title)->toBe('Volei de quarta');
    actingAsWorkspace(1, ['id' => $host->user_id, 'is_adult' => true])
        ->patchJson("/api/sessions/{$first->id}/occurrence", ['version' => 1, 'title' => 'Conflito'])
        ->assertConflict();
});

it('updates future materialized occurrences without losing their participant records and cancels one occurrence', function () {
    $host = SportProfile::query()->create(['user_id' => 2811, 'display_name' => 'Nina', 'city' => 'Sao Paulo', 'region' => 'SP']);
    $participant = SportProfile::query()->create(['user_id' => 2812, 'display_name' => 'Caio', 'city' => 'Sao Paulo', 'region' => 'SP']);
    $sport = Sport::query()->create(['name' => 'Basquete', 'slug' => 'basquete']);
    $series = managedSeries($host, $sport);
    $first = managedOccurrence($series, '2026-10-21T19:00:00-03:00', '2026-10-21 22:00:00+00:00');
    $future = managedOccurrence($series, '2026-10-28T19:00:00-03:00', '2026-10-28 22:00:00+00:00');
    DB::table('session_participants')->insert(['sport_session_id' => $future->id, 'sport_profile_id' => $participant->id, 'status' => 'joined', 'created_at' => now(), 'updated_at' => now()]);

    actingAsWorkspace(1, ['id' => $host->user_id, 'is_adult' => true])
        ->patchJson("/api/sessions/{$first->id}/series-from", ['version' => 1, 'series_version' => 1, 'title' => 'Basquete no ginasio', 'starts_at_local' => '20:00'])
        ->assertOk();

    expect($future->fresh()->title)->toBe('Basquete no ginasio')
        ->and($future->fresh()->starts_at->setTimezone('America/Sao_Paulo')->format('H:i'))->toBe('20:00')
        ->and(DB::table('session_participants')->where('sport_session_id', $future->id)->where('sport_profile_id', $participant->id)->value('status'))->toBe('joined');

    actingAsWorkspace(1, ['id' => $host->user_id, 'is_adult' => true])
        ->postJson("/api/sessions/{$future->id}/cancel", ['version' => 2, 'reason' => 'Quadra interditada'])
        ->assertOk()->assertJsonPath('data.status', 'cancelled')->assertJsonPath('data.change_notice', 'cancelled');
    expect($series->fresh()->status)->toBe('active');
});
