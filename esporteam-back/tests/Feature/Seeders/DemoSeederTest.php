<?php

use App\Models\ClassOffering;
use App\Models\Connection;
use App\Models\Report;
use App\Models\SessionParticipant;
use App\Models\Sport;
use App\Models\SportProfile;
use App\Models\SportSession;
use App\Models\TeacherProfile;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Schema;

it('seeds the complete sport discovery demo dataset idempotently', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    $practitioner = SportProfile::query()
        ->with(['teacherProfile'])
        ->where('user_id', 8001)
        ->firstOrFail();

    expect(Sport::query()->count())->toBe(20)
        ->and(Sport::query()->where('is_active', true)->count())->toBe(20)
        ->and(SportProfile::query()->whereBetween('user_id', [8001, 8040])->count())->toBe(40)
        ->and($practitioner->display_name)->toBe('Ana Martins')
        ->and($practitioner->bio)->toContain('ana.praticante@esporteam.test')
        ->and($practitioner->teacherProfile)->toBeNull()
        ->and(TeacherProfile::query()->count())->toBe(8)
        ->and(ClassOffering::query()->where('status', 'open')->count())->toBe(15)
        ->and(SportSession::query()->where('status', 'open')->where('visibility', 'public')->count())->toBe(20)
        ->and(SportSession::query()->where('entry_mode', 'publica_direta')->count())->toBeGreaterThanOrEqual(10)
        ->and(SportSession::query()->where('entry_mode', 'publica_aprovacao')->where('requires_approval', true)->count())->toBeGreaterThanOrEqual(4)
        ->and(SportSession::query()->where('entry_mode', 'convite')->count())->toBeGreaterThanOrEqual(3)
        ->and(SportSession::query()->whereNotNull('min_level')->whereNotNull('max_level')->count())->toBeGreaterThanOrEqual(8)
        ->and(SessionParticipant::query()->where('status', 'joined')->count())->toBeGreaterThanOrEqual(25)
        ->and(SessionParticipant::query()->where('status', 'approved')->count())->toBeGreaterThanOrEqual(4)
        ->and(SessionParticipant::query()->where('status', 'interested')->count())->toBeGreaterThanOrEqual(2)
        ->and(SessionParticipant::query()->where('status', 'invited')->count())->toBeGreaterThanOrEqual(2)
        ->and(SessionParticipant::query()->where('status', 'declined')->count())->toBeGreaterThanOrEqual(1)
        ->and(Connection::query()->where('type', 'friendship')->count())->toBeGreaterThanOrEqual(3)
        ->and(Connection::query()->where('type', 'interest')->count())->toBeGreaterThanOrEqual(2)
        ->and(Connection::query()->where('type', 'block')->where('status', 'blocked')->count())->toBeGreaterThanOrEqual(2)
        ->and(Report::query()->where('status', 'open')->count())->toBeGreaterThanOrEqual(2)
        ->and(Schema::hasColumn('sport_sessions', 'price_cents'))->toBeFalse()
        ->and(Schema::hasColumn('sport_sessions', 'price'))->toBeFalse();

    expect(SportProfile::query()->whereDoesntHave('sports')->count())->toBe(0)
        ->and(SportProfile::query()->whereDoesntHave('availabilityWindows')->count())->toBe(0)
        ->and(ClassOffering::query()->whereNull('price_cents')->count())->toBe(0)
        ->and(SportSession::query()->whereNull('capacity')->count())->toBe(0);

    expect(SessionParticipant::query()
        ->where('sport_profile_id', $practitioner->id)
        ->whereIn('status', ['joined', 'interested', 'invited', 'declined'])
        ->count())->toBeGreaterThanOrEqual(4);
});
