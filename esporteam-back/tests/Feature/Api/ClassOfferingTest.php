<?php

use App\Models\ClassOffering;
use App\Models\Sport;
use App\Models\SportProfile;
use App\Models\TeacherProfile;
use Illuminate\Support\Facades\DB;

function createClassSportProfileForUser(int $userId, string $name, array $attributes = []): SportProfile
{
    return SportProfile::query()->create(array_merge([
        'user_id' => $userId,
        'display_name' => $name,
    ], $attributes));
}

function createTeacherForUser(int $userId, string $name = 'Teacher'): TeacherProfile
{
    $profile = createClassSportProfileForUser($userId, $name);

    return TeacherProfile::query()->create([
        'sport_profile_id' => $profile->id,
        'headline' => 'Professor',
    ]);
}

it('creates a class offering for the authenticated teacher profile', function () {
    $teacher = createTeacherForUser(77);
    $otherTeacher = createTeacherForUser(88, 'Other teacher');
    $sport = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);

    actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/classes', [
            'teacher_profile_id' => $otherTeacher->id,
            'sport_id' => $sport->id,
            'title' => 'Aula de saque',
            'description' => 'Tecnica e repeticao.',
            'price_cents' => 12000,
            'starts_at' => now()->addDay()->setSecond(0)->toISOString(),
            'recurrence' => 'weekly',
            'location_label' => 'Clube municipal',
            'city' => 'Sao Paulo',
            'region' => 'SP',
            'latitude_approx' => -23.55,
            'longitude_approx' => -46.63,
            'capacity' => 4,
        ])
        ->assertCreated()
        ->assertJsonPath('data.teacher_profile_id', $teacher->id)
        ->assertJsonPath('data.sport_id', $sport->id)
        ->assertJsonPath('data.title', 'Aula de saque')
        ->assertJsonPath('data.price_cents', 12000)
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.interest_count', 0);

    expect(ClassOffering::query()->where('teacher_profile_id', $otherTeacher->id)->exists())->toBeFalse();
});

it('lists open class offerings by sport price schedule and distance', function () {
    $sport = Sport::query()->create(['name' => 'Tenis', 'slug' => 'tenis']);
    $otherSport = Sport::query()->create(['name' => 'Corrida', 'slug' => 'corrida']);
    $teacher = createTeacherForUser(77);

    createClassSportProfileForUser(99, 'Student', [
        'latitude_approx' => -23.550,
        'longitude_approx' => -46.633,
    ]);

    $matching = ClassOffering::query()->create([
        'teacher_profile_id' => $teacher->id,
        'sport_id' => $sport->id,
        'title' => 'Tenis perto',
        'price_cents' => 9000,
        'starts_at' => now()->addDays(2)->setSecond(0),
        'city' => 'Sao Paulo',
        'region' => 'SP',
        'latitude_approx' => -23.551,
        'longitude_approx' => -46.634,
        'capacity' => 8,
        'status' => 'open',
    ]);

    ClassOffering::query()->create([
        'teacher_profile_id' => $teacher->id,
        'sport_id' => $sport->id,
        'title' => 'Tenis caro',
        'price_cents' => 20000,
        'starts_at' => now()->addDays(2)->setSecond(0),
        'city' => 'Sao Paulo',
        'latitude_approx' => -23.552,
        'longitude_approx' => -46.635,
        'status' => 'open',
    ]);

    ClassOffering::query()->create([
        'teacher_profile_id' => $teacher->id,
        'sport_id' => $otherSport->id,
        'title' => 'Corrida',
        'price_cents' => 5000,
        'starts_at' => now()->addDays(2)->setSecond(0),
        'city' => 'Sao Paulo',
        'status' => 'open',
    ]);

    ClassOffering::query()->create([
        'teacher_profile_id' => $teacher->id,
        'sport_id' => $sport->id,
        'title' => 'Cancelada',
        'price_cents' => 8000,
        'starts_at' => now()->addDays(2)->setSecond(0),
        'city' => 'Sao Paulo',
        'status' => 'cancelled',
    ]);

    actingAsWorkspace(1, ['id' => 99])
        ->getJson('/api/classes?sport_slug=tenis&max_price_cents=10000&city=Sao%20Paulo&distance_km=5')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matching->id)
        ->assertJsonPath('data.0.sport.slug', 'tenis')
        ->assertJsonPath('data.0.teacher.id', $teacher->id);
});

it('registers student interest in a class once', function () {
    $teacher = createTeacherForUser(77);
    $student = createClassSportProfileForUser(88, 'Student');
    $sport = Sport::query()->create(['name' => 'Yoga', 'slug' => 'yoga']);
    $class = ClassOffering::query()->create([
        'teacher_profile_id' => $teacher->id,
        'sport_id' => $sport->id,
        'title' => 'Yoga iniciante',
        'starts_at' => now()->addDay()->setSecond(0),
        'capacity' => 2,
        'status' => 'open',
    ]);

    actingAsWorkspace(1, ['id' => 88])
        ->postJson("/api/classes/{$class->id}/interest")
        ->assertCreated()
        ->assertJsonPath('data.interest_count', 1)
        ->assertJsonPath('data.interested_profiles.0.id', $student->id);

    actingAsWorkspace(1, ['id' => 88])
        ->postJson("/api/classes/{$class->id}/interest")
        ->assertUnprocessable();

    expect(DB::table('class_interests')
        ->where('class_offering_id', $class->id)
        ->where('sport_profile_id', $student->id)
        ->where('status', 'interested')
        ->exists())->toBeTrue();
});

it('rejects interest when class capacity status or ownership does not allow it', function () {
    $teacher = createTeacherForUser(77);
    createClassSportProfileForUser(88, 'Candidate A');
    createClassSportProfileForUser(99, 'Candidate B');
    $sport = Sport::query()->create(['name' => 'Funcional', 'slug' => 'funcional']);

    $fullClass = ClassOffering::query()->create([
        'teacher_profile_id' => $teacher->id,
        'sport_id' => $sport->id,
        'title' => 'Funcional cheio',
        'starts_at' => now()->addDay()->setSecond(0),
        'capacity' => 1,
        'status' => 'open',
    ]);

    actingAsWorkspace(1, ['id' => 88])
        ->postJson("/api/classes/{$fullClass->id}/interest")
        ->assertCreated();

    actingAsWorkspace(1, ['id' => 99])
        ->postJson("/api/classes/{$fullClass->id}/interest")
        ->assertUnprocessable();

    $cancelledClass = ClassOffering::query()->create([
        'teacher_profile_id' => $teacher->id,
        'sport_id' => $sport->id,
        'title' => 'Funcional cancelado',
        'starts_at' => now()->addDay()->setSecond(0),
        'status' => 'cancelled',
    ]);

    actingAsWorkspace(1, ['id' => 99])
        ->postJson("/api/classes/{$cancelledClass->id}/interest")
        ->assertUnprocessable();

    actingAsWorkspace(1, ['id' => 77])
        ->postJson("/api/classes/{$fullClass->id}/interest")
        ->assertUnprocessable();
});
