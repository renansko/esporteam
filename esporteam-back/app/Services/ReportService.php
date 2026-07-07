<?php

namespace App\Services;

use App\Models\Report;
use App\Models\SportProfile;

class ReportService
{
    public function createForUser(int $userId, int $reportedProfileId, string $reason, ?string $details = null): Report
    {
        $reporter = $this->requireProfile($userId);

        if ($reporter->id === $reportedProfileId) {
            abort(422, 'Cannot report the same profile.');
        }

        $reported = SportProfile::query()->findOrFail($reportedProfileId);

        $report = Report::query()->create([
            'reporter_profile_id' => $reporter->id,
            'reported_profile_id' => $reported->id,
            'reason' => $reason,
            'details' => $details,
            'status' => 'open',
            'context' => [
                'reporter' => $this->profileContext($reporter),
                'reported' => $this->profileContext($reported),
            ],
        ]);

        return $report->load(['reporter', 'reported']);
    }

    private function requireProfile(int $userId): SportProfile
    {
        return SportProfile::query()
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    private function profileContext(SportProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'display_name' => $profile->display_name,
            'city' => $profile->city,
            'region' => $profile->region,
            'visibility' => $profile->visibility?->value,
        ];
    }
}
