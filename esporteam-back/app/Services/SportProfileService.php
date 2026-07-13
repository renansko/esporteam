<?php

namespace App\Services;

use App\Enums\ProfileVisibility;
use App\Models\SportProfile;
use Illuminate\Support\Facades\DB;

class SportProfileService
{
    public function __construct(
        private readonly ProfileBioEmbeddingService $bioEmbeddings,
    ) {}

    public function findForUser(int $userId): ?SportProfile
    {
        return SportProfile::query()
            ->with(['sports.sport', 'availabilityWindows'])
            ->where('user_id', $userId)
            ->first();
    }

    public function upsertForUser(int $userId, array $data): SportProfile
    {
        $profile = DB::transaction(function () use ($userId, $data): SportProfile {
            $existingProfile = SportProfile::query()
                ->where('user_id', $userId)
                ->first(['bio', 'bio_assistant_onboarding_completed_at']);
            $previousBio = $existingProfile?->bio;
            $bio = $data['bio'] ?? null;
            $profile = SportProfile::query()->updateOrCreate(
                ['user_id' => $userId],
                [
                    'display_name' => $data['display_name'],
                    'bio' => $bio,
                    'city' => $data['city'] ?? null,
                    'region' => $data['region'] ?? null,
                    'latitude_approx' => $this->approximateCoordinate($data['latitude_approx'] ?? null),
                    'longitude_approx' => $this->approximateCoordinate($data['longitude_approx'] ?? null),
                    'visibility' => $data['visibility'] ?? ProfileVisibility::Public->value,
                    'avatar_url' => $data['avatar_url'] ?? null,
                    'bio_assistant_onboarding_completed_at' => $existingProfile?->bio_assistant_onboarding_completed_at
                        ?? (filled($bio) ? now() : null),
                ],
            );

            if ($previousBio !== $profile->bio) {
                $this->bioEmbeddings->synchronize($profile);
            }

            return $profile;
        });

        return $profile->load(['sports.sport', 'availabilityWindows']);
    }

    public function replaceSports(int $userId, array $sports): SportProfile
    {
        return DB::transaction(function () use ($userId, $sports) {
            $profile = $this->requireProfile($userId);
            $profile->sports()->delete();

            foreach ($sports as $sport) {
                $profile->sports()->create([
                    'sport_id' => $sport['sport_id'],
                    'level' => $sport['level'],
                    'goals' => $sport['goals'] ?? [],
                    'preferred_positions' => $sport['preferred_positions'] ?? null,
                    'is_primary' => (bool) ($sport['is_primary'] ?? false),
                ]);
            }

            return $profile->load(['sports.sport', 'availabilityWindows']);
        });
    }

    public function replaceAvailability(int $userId, array $windows): SportProfile
    {
        return DB::transaction(function () use ($userId, $windows) {
            $profile = $this->requireProfile($userId);
            $profile->availabilityWindows()->delete();

            foreach ($windows as $window) {
                $profile->availabilityWindows()->create($window);
            }

            return $profile->load(['sports.sport', 'availabilityWindows']);
        });
    }

    private function requireProfile(int $userId): SportProfile
    {
        return SportProfile::query()
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    private function approximateCoordinate(mixed $coordinate): ?float
    {
        if ($coordinate === null) {
            return null;
        }

        return round((float) $coordinate, 3);
    }
}
