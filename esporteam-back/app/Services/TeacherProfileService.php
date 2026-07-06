<?php

namespace App\Services;

use App\Models\SportProfile;
use App\Models\TeacherProfile;
use Illuminate\Support\Facades\DB;

class TeacherProfileService
{
    public function findForUser(int $userId): ?TeacherProfile
    {
        return TeacherProfile::query()
            ->with(['profile', 'students'])
            ->whereHas('profile', fn ($query) => $query->where('user_id', $userId))
            ->first();
    }

    public function upsertForUser(int $userId, array $data): TeacherProfile
    {
        $profile = $this->requireProfile($userId);

        $teacher = TeacherProfile::query()->updateOrCreate(
            ['sport_profile_id' => $profile->id],
            [
                'headline' => $data['headline'] ?? null,
                'credentials' => $data['credentials'] ?? null,
                'hourly_price_cents' => $data['hourly_price_cents'] ?? null,
                'service_radius_km' => $data['service_radius_km'] ?? null,
            ],
        );

        return $teacher->load(['profile', 'students']);
    }

    public function addStudent(int $userId, int $studentProfileId, string $status = 'active'): TeacherProfile
    {
        $teacher = $this->requireTeacherForUser($userId);

        if ($teacher->sport_profile_id === $studentProfileId) {
            abort(422, 'Teacher cannot add their own profile as a student.');
        }

        if ($teacher->students()->whereKey($studentProfileId)->exists()) {
            abort(422, 'Student already exists for this teacher.');
        }

        $teacher->students()->attach($studentProfileId, [
            'status' => $status,
            'started_at' => $status === 'active' ? now() : null,
        ]);

        return $teacher->load(['profile', 'students']);
    }

    public function endStudent(int $userId, SportProfile $studentProfile): void
    {
        $teacher = $this->requireTeacherForUser($userId);

        $updated = DB::table('teacher_students')
            ->where('teacher_profile_id', $teacher->id)
            ->where('student_profile_id', $studentProfile->id)
            ->update([
                'status' => 'ended',
                'ended_at' => now(),
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            abort(404, 'Student relationship not found.');
        }
    }

    private function requireTeacherForUser(int $userId): TeacherProfile
    {
        return TeacherProfile::query()
            ->whereHas('profile', fn ($query) => $query->where('user_id', $userId))
            ->firstOrFail();
    }

    private function requireProfile(int $userId): SportProfile
    {
        return SportProfile::query()
            ->where('user_id', $userId)
            ->firstOrFail();
    }
}
