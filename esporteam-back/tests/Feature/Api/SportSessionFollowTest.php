<?php

use App\Models\Sport;
use App\Models\SportProfile;
use App\Models\SportSession;
use App\Models\SportSessionSeries;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => config()->set('features.recurring_events', true));

it('follows a series without creating occurrence participation and keeps joins occurrence-scoped', function () {
    $host = SportProfile::query()->create(['user_id' => 1101, 'display_name' => 'Host']);
    $follower = SportProfile::query()->create(['user_id' => 1102, 'display_name' => 'Follower']);
    $sport = Sport::query()->create(['name' => 'Volei', 'slug' => 'volei']);
    $series = SportSessionSeries::query()->create([
        'creator_profile_id' => $host->id, 'sport_id' => $sport->id, 'title' => 'Volei semanal', 'type' => 'partida',
        'starts_on' => now()->toDateString(), 'starts_at_local' => '19:00:00', 'duration_minutes' => 90, 'timezone' => 'America/Sao_Paulo',
        'interval_weeks' => 1, 'weekdays' => [1], 'ends_type' => 'never', 'location_label_public' => 'Parque', 'meeting_point_label' => 'Portao 3',
        'city' => 'Sao Paulo', 'region' => 'SP', 'latitude_approx' => -23.5, 'longitude_approx' => -46.6, 'latitude_exact' => -23.5001, 'longitude_exact' => -46.6001,
        'entry_mode' => 'publica_direta', 'visibility' => 'public', 'status' => 'active', 'publication_key' => 'follow-1101',
    ]);
    $occurrence = SportSession::query()->create([
        'sport_session_series_id' => $series->id, 'creator_profile_id' => $host->id, 'sport_id' => $sport->id, 'title' => 'Volei semanal', 'type' => 'partida',
        'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addMinutes(90), 'timezone' => 'America/Sao_Paulo', 'location_label' => 'Parque',
        'location_label_public' => 'Parque', 'meeting_point_label' => 'Portao 3', 'city' => 'Sao Paulo', 'region' => 'SP', 'latitude_approx' => -23.5, 'longitude_approx' => -46.6,
        'latitude_exact' => -23.5001, 'longitude_exact' => -46.6001, 'entry_mode' => 'publica_direta', 'visibility' => 'public', 'status' => 'open', 'occurrence_key' => 'one',
    ]);

    actingAsWorkspace(1, ['id' => 1102, 'is_adult' => true])->postJson("/api/session-series/{$series->id}/follow")->assertCreated();
    actingAsWorkspace(1, ['id' => 1102, 'is_adult' => true])->postJson("/api/session-series/{$series->id}/follow")->assertCreated();
    expect(DB::table('sport_session_series_followers')->count())->toBe(1)
        ->and(DB::table('session_participants')->where('sport_profile_id', $follower->id)->count())->toBe(0);

    actingAsWorkspace(1, ['id' => 1102, 'is_adult' => true])->postJson("/api/sessions/{$occurrence->id}/join")->assertCreated();
    expect(DB::table('session_participants')->where('sport_session_id', $occurrence->id)->where('sport_profile_id', $follower->id)->exists())->toBeTrue();
    actingAsWorkspace(1, ['id' => 1102, 'is_adult' => true])->deleteJson("/api/session-series/{$series->id}/follow")->assertOk();
    expect(DB::table('session_participants')->where('sport_session_id', $occurrence->id)->where('sport_profile_id', $follower->id)->exists())->toBeTrue();
});
