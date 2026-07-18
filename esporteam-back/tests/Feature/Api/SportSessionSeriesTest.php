<?php

use App\Jobs\MaterializeSportSessionSeries;
use App\Models\Sport;
use App\Models\SportProfile;
use App\Models\SportSession;
use App\Models\SportSessionSeries;
use App\Services\SportSessionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;

function seriesPayload(Sport $sport, array $overrides = []): array
{
    return array_merge([
        'sport_id' => $sport->id, 'title' => 'Corrida semanal no parque', 'type' => 'corrida',
        'starts_on' => '2026-10-26', 'starts_at_local' => '19:00', 'duration_minutes' => 90,
        'timezone' => 'America/Sao_Paulo', 'interval_weeks' => 1, 'weekdays' => [1, 3], 'ends_type' => 'never',
        'meeting_point_label' => 'Portao 3', 'location_label_public' => 'Parque Ibirapuera',
        'city' => 'Sao Paulo', 'region' => 'SP', 'latitude' => -23.587421, 'longitude' => -46.657921,
        'entry_mode' => 'publica_direta', 'visibility' => 'public',
    ], $overrides);
}

beforeEach(function () {
    config()->set('features.recurring_events', true);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-25 12:00:00', 'UTC'));
});

afterEach(fn () => CarbonImmutable::setTestNow());

it('publishes a weekly series idempotently and materializes its discovery occurrences', function () {
    $host = SportProfile::query()->create(['user_id' => 901, 'display_name' => 'Lia', 'city' => 'Sao Paulo', 'region' => 'SP']);
    $sport = Sport::query()->create(['name' => 'Corrida', 'slug' => 'corrida']);
    $payload = seriesPayload($sport);

    $first = actingAsWorkspace(1, ['id' => 901, 'is_adult' => true])
        ->withHeader('Idempotency-Key', 'series-corrida-901')
        ->postJson('/api/sessions/publish-series', $payload)
        ->assertCreated()
        ->assertJsonPath('data.series.timezone', 'America/Sao_Paulo')
        ->assertJsonCount(26, 'data.occurrences')
        ->json('data.series.id');

    actingAsWorkspace(1, ['id' => 901, 'is_adult' => true])
        ->withHeader('Idempotency-Key', 'series-corrida-901')
        ->postJson('/api/sessions/publish-series', $payload)
        ->assertCreated()
        ->assertJsonPath('data.series.id', $first);

    expect(SportSessionSeries::query()->count())->toBe(1)
        ->and(SportSession::query()->where('sport_session_series_id', $first)->count())->toBe(26);

    actingAsWorkspace(1, ['id' => 902])
        ->getJson('/api/sessions')
        ->assertOk()
        ->assertJsonPath('data.0.series.id', $first)
        ->assertJsonMissingPath('data.0.meeting_point');

    config()->set('features.recurring_events', false);
    actingAsWorkspace(1, ['id' => 901, 'is_adult' => true])
        ->withHeader('Idempotency-Key', 'disabled-series-901')
        ->postJson('/api/sessions/publish-series', $payload)
        ->assertUnprocessable();
    actingAsWorkspace(1, ['id' => 902])
        ->getJson('/api/sessions')
        ->assertOk()
        ->assertJsonMissingPath('data.0.series');
});

it('preserves local wall time through DST and respects count termination', function () {
    $host = SportProfile::query()->create(['user_id' => 903, 'display_name' => 'Rui', 'city' => 'New York', 'region' => 'NY']);
    $sport = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);

    $id = actingAsWorkspace(1, ['id' => 903, 'is_adult' => true])
        ->withHeader('Idempotency-Key', 'series-tennis-903')
        ->postJson('/api/sessions/publish-series', seriesPayload($sport, [
            'timezone' => 'America/New_York', 'starts_on' => '2026-10-26', 'starts_at_local' => '19:00',
            'weekdays' => [1], 'ends_type' => 'count', 'occurrence_count' => 3,
        ]))
        ->assertCreated()->assertJsonCount(3, 'data.occurrences')->json('data.series.id');

    $occurrences = SportSession::query()->where('sport_session_series_id', $id)->orderBy('starts_at')->get();
    expect($occurrences)->toHaveCount(3)
        ->and($occurrences->every(fn (SportSession $session) => $session->ends_at->greaterThan($session->starts_at)))->toBeTrue()
        ->and($occurrences->map(fn (SportSession $session) => $session->starts_at->setTimezone('America/New_York')->format('H:i'))->all())
        ->toBe(['19:00', '19:00', '19:00']);
});

it('materializes only matching weekdays for a biweekly series ending on a date', function () {
    SportProfile::query()->create(['user_id' => 905, 'display_name' => 'Caio', 'city' => 'Sao Paulo', 'region' => 'SP']);
    $sport = Sport::query()->create(['name' => 'Basquete', 'slug' => 'basquete']);

    $id = actingAsWorkspace(1, ['id' => 905, 'is_adult' => true])
        ->withHeader('Idempotency-Key', 'series-basket-905')
        ->postJson('/api/sessions/publish-series', seriesPayload($sport, [
            'starts_on' => '2026-10-26', 'interval_weeks' => 2, 'weekdays' => [1, 3],
            'ends_type' => 'date', 'ends_on' => '2026-11-11',
        ]))
        ->assertCreated()->assertJsonCount(4, 'data.occurrences')->json('data.series.id');

    expect(SportSession::query()->where('sport_session_series_id', $id)->orderBy('starts_at')->get()
        ->map(fn (SportSession $session) => $session->starts_at->setTimezone('America/Sao_Paulo')->toDateString())->all())
        ->toBe(['2026-10-26', '2026-10-28', '2026-11-09', '2026-11-11']);
});

it('lets the scheduled job repair an active series horizon without duplication', function () {
    Queue::fake();
    $host = SportProfile::query()->create(['user_id' => 904, 'display_name' => 'Nina', 'city' => 'Sao Paulo', 'region' => 'SP']);
    $sport = Sport::query()->create(['name' => 'Volei', 'slug' => 'volei']);
    $series = SportSessionSeries::query()->create(array_merge(seriesPayload($sport), [
        'creator_profile_id' => $host->id, 'starts_at_local' => '19:00:00', 'starts_on' => '2026-10-26',
        'duration_minutes' => 60, 'latitude_approx' => -23.587, 'longitude_approx' => -46.658,
        'latitude_exact' => -23.587421, 'longitude_exact' => -46.657921, 'status' => 'active',
        'publication_key' => 'repair-904',
    ]));

    (new MaterializeSportSessionSeries($series->id))->handle(app(SportSessionService::class));
    $count = SportSession::query()->where('sport_session_series_id', $series->id)->count();
    (new MaterializeSportSessionSeries($series->id))->handle(app(SportSessionService::class));

    expect($count)->toBeGreaterThan(0)
        ->and(SportSession::query()->where('sport_session_series_id', $series->id)->count())->toBe($count);
});
