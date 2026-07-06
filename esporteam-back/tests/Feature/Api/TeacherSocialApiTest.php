<?php

use App\Models\Connection;
use App\Models\SportGroup;
use App\Models\SportProfile;
use App\Models\TeacherProfile;
use Illuminate\Support\Facades\DB;

function createSportProfileForUser(int $userId, string $name): SportProfile
{
    return SportProfile::query()->create([
        'user_id' => $userId,
        'display_name' => $name,
    ]);
}

it('creates and updates a teacher profile for the authenticated sport profile', function () {
    $profile = createSportProfileForUser(77, 'Teacher');
    $otherProfile = createSportProfileForUser(999, 'Other');

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/teacher-profile', [
            'user_id' => 999,
            'sport_profile_id' => $otherProfile->id,
            'headline' => 'Treinadora de corrida',
            'credentials' => 'CREF ativo',
            'hourly_price_cents' => 12000,
            'service_radius_km' => 15,
        ])
        ->assertOk()
        ->assertJsonPath('data.sport_profile_id', $profile->id)
        ->assertJsonPath('data.headline', 'Treinadora de corrida');

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/teacher-profile', [
            'headline' => 'Treinadora de tenis',
        ])
        ->assertOk()
        ->assertJsonPath('data.sport_profile_id', $profile->id)
        ->assertJsonPath('data.headline', 'Treinadora de tenis');

    expect(TeacherProfile::query()->where('sport_profile_id', $profile->id)->count())->toBe(1);
    expect(TeacherProfile::query()->where('sport_profile_id', $otherProfile->id)->exists())->toBeFalse();
});

it('requires the authenticated user to have a sport profile before creating teacher group or connection records', function () {
    $target = createSportProfileForUser(88, 'Target');

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/teacher-profile', ['headline' => 'Teacher'])
        ->assertNotFound();

    actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/groups', ['name' => 'Grupo sem perfil'])
        ->assertNotFound();

    actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/connections', [
            'target_profile_id' => $target->id,
            'type' => 'friendship',
        ])
        ->assertNotFound();
});

it('adds students to a teacher and allows the same student to have multiple teachers', function () {
    $student = createSportProfileForUser(10, 'Student');
    createSportProfileForUser(77, 'Teacher A');
    createSportProfileForUser(88, 'Teacher B');

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/teacher-profile', ['headline' => 'Teacher A'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 88])
        ->putJson('/api/teacher-profile', ['headline' => 'Teacher B'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/teacher-profile/students', ['student_profile_id' => $student->id])
        ->assertCreated()
        ->assertJsonPath('data.students.0.id', $student->id);

    actingAsWorkspace(1, ['id' => 88])
        ->postJson('/api/teacher-profile/students', ['student_profile_id' => $student->id])
        ->assertCreated();

    expect(DB::table('teacher_students')->where('student_profile_id', $student->id)->count())->toBe(2);
});

it('rejects duplicated students for the same teacher', function () {
    $student = createSportProfileForUser(10, 'Student');
    createSportProfileForUser(77, 'Teacher');

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/teacher-profile', ['headline' => 'Teacher'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/teacher-profile/students', ['student_profile_id' => $student->id])
        ->assertCreated();

    actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/teacher-profile/students', ['student_profile_id' => $student->id])
        ->assertUnprocessable();
});

it('does not let a teacher manage another teachers student relationship', function () {
    $student = createSportProfileForUser(10, 'Student');
    createSportProfileForUser(77, 'Teacher A');
    createSportProfileForUser(88, 'Teacher B');

    actingAsWorkspace(1, ['id' => 77])
        ->putJson('/api/teacher-profile', ['headline' => 'Teacher A'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 88])
        ->putJson('/api/teacher-profile', ['headline' => 'Teacher B'])
        ->assertOk();

    actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/teacher-profile/students', ['student_profile_id' => $student->id])
        ->assertCreated();

    actingAsWorkspace(1, ['id' => 88])
        ->deleteJson("/api/teacher-profile/students/{$student->id}")
        ->assertNotFound();

    expect(DB::table('teacher_students')
        ->where('student_profile_id', $student->id)
        ->where('status', 'active')
        ->exists())->toBeTrue();
});

it('creates a group with the creator as owner', function () {
    $creator = createSportProfileForUser(77, 'Creator');
    $otherProfile = createSportProfileForUser(999, 'Other');

    actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/groups', [
            'creator_profile_id' => $otherProfile->id,
            'name' => 'Corrida no parque',
            'description' => 'Treinos aos sabados',
            'visibility' => 'public',
            'capacity' => 20,
        ])
        ->assertCreated()
        ->assertJsonPath('data.creator_profile_id', $creator->id)
        ->assertJsonPath('data.members.0.id', $creator->id);

    expect(DB::table('sport_group_members')
        ->where('sport_profile_id', $creator->id)
        ->where('role', 'owner')
        ->where('status', 'active')
        ->exists())->toBeTrue();
    expect(SportGroup::query()->where('creator_profile_id', $otherProfile->id)->exists())->toBeFalse();
});

it('lets owners and admins manage group members but rejects regular members', function () {
    $owner = createSportProfileForUser(77, 'Owner');
    $admin = createSportProfileForUser(88, 'Admin');
    $member = createSportProfileForUser(99, 'Member');
    $candidate = createSportProfileForUser(100, 'Candidate');

    $groupId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/groups', ['name' => 'Pelada'])
        ->assertCreated()
        ->json('data.id');

    $group = SportGroup::query()->findOrFail($groupId);

    actingAsWorkspace(1, ['id' => 77])
        ->postJson("/api/groups/{$group->id}/members", [
            'sport_profile_id' => $admin->id,
            'role' => 'admin',
        ])
        ->assertCreated();

    actingAsWorkspace(1, ['id' => 77])
        ->postJson("/api/groups/{$group->id}/members", [
            'sport_profile_id' => $member->id,
            'role' => 'member',
        ])
        ->assertCreated();

    actingAsWorkspace(1, ['id' => 88])
        ->postJson("/api/groups/{$group->id}/members", ['sport_profile_id' => $candidate->id])
        ->assertCreated();

    actingAsWorkspace(1, ['id' => 99])
        ->deleteJson("/api/groups/{$group->id}/members/{$candidate->id}")
        ->assertForbidden();

    actingAsWorkspace(1, ['id' => 77])
        ->deleteJson("/api/groups/{$group->id}/members/{$candidate->id}")
        ->assertNoContent();

    expect(DB::table('sport_group_members')
        ->where('sport_group_id', $group->id)
        ->where('sport_profile_id', $candidate->id)
        ->value('status'))->toBe('left');

    expect($owner->id)->toBeInt();
});

it('rejects group management from profiles that are not active owners or admins', function () {
    $member = createSportProfileForUser(99, 'Member');
    $candidate = createSportProfileForUser(100, 'Candidate');
    createSportProfileForUser(77, 'Owner');
    createSportProfileForUser(101, 'Outsider');

    $groupId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/groups', ['name' => 'Tenis'])
        ->assertCreated()
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 77])
        ->postJson("/api/groups/{$groupId}/members", ['sport_profile_id' => $member->id])
        ->assertCreated();

    actingAsWorkspace(1, ['id' => 101])
        ->postJson("/api/groups/{$groupId}/members", ['sport_profile_id' => $candidate->id])
        ->assertForbidden();

    actingAsWorkspace(1, ['id' => 99])
        ->postJson("/api/groups/{$groupId}/members", ['sport_profile_id' => $candidate->id])
        ->assertForbidden();

    expect(DB::table('sport_group_members')
        ->where('sport_group_id', $groupId)
        ->where('sport_profile_id', $candidate->id)
        ->exists())->toBeFalse();
});

it('creates accepts and declines friendship requests without duplicated pairs', function () {
    $requester = createSportProfileForUser(77, 'Requester');
    $target = createSportProfileForUser(88, 'Target');
    createSportProfileForUser(99, 'Third Party');

    $connectionId = actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/connections', [
            'requester_profile_id' => $target->id,
            'target_profile_id' => $target->id,
            'type' => 'friendship',
        ])
        ->assertCreated()
        ->assertJsonPath('data.requester_profile_id', $requester->id)
        ->assertJsonPath('data.status', 'pending')
        ->json('data.id');

    actingAsWorkspace(1, ['id' => 77])
        ->patchJson("/api/connections/{$connectionId}", ['status' => 'accepted'])
        ->assertForbidden();

    actingAsWorkspace(1, ['id' => 99])
        ->patchJson("/api/connections/{$connectionId}", ['status' => 'accepted'])
        ->assertForbidden();

    actingAsWorkspace(1, ['id' => 88])
        ->patchJson("/api/connections/{$connectionId}", ['status' => 'accepted'])
        ->assertOk()
        ->assertJsonPath('data.status', 'accepted');

    actingAsWorkspace(1, ['id' => 88])
        ->postJson('/api/connections', [
            'target_profile_id' => $requester->id,
            'type' => 'friendship',
        ])
        ->assertUnprocessable();
});

it('removes active friendship between profiles when a block is created', function () {
    $requester = createSportProfileForUser(77, 'Requester');
    $target = createSportProfileForUser(88, 'Target');

    $connection = Connection::query()->create([
        'requester_profile_id' => $requester->id,
        'target_profile_id' => $target->id,
        'profile_low_id' => min($requester->id, $target->id),
        'profile_high_id' => max($requester->id, $target->id),
        'type' => 'friendship',
        'status' => 'accepted',
    ]);

    actingAsWorkspace(1, ['id' => 77])
        ->postJson('/api/connections', [
            'target_profile_id' => $target->id,
            'type' => 'block',
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'block')
        ->assertJsonPath('data.status', 'blocked');

    expect(Connection::query()->whereKey($connection->id)->exists())->toBeFalse();

    actingAsWorkspace(1, ['id' => 88])
        ->postJson('/api/connections', [
            'target_profile_id' => $requester->id,
            'type' => 'friendship',
        ])
        ->assertUnprocessable();
});
