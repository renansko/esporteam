<?php

namespace App\Services;

use App\Enums\ProfileVisibility;
use App\Models\Connection;
use App\Models\SportProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @wiki app/brain/services/DiscoveryService.md
 */
class DiscoveryService
{
    /**
     * @wiki app/brain/functions/DiscoveryService.md#profilesForUser
     */
    public function profilesForUser(int $userId, array $filters = []): Collection
    {
        $currentProfile = SportProfile::query()
            ->with(['sports', 'availabilityWindows'])
            ->where('user_id', $userId)
            ->first();
        $blockedProfileIds = $currentProfile === null ? [] : $this->blockedProfileIds($currentProfile->id);

        $profiles = SportProfile::query()
            ->with(['sports.sport', 'availabilityWindows', 'teacherProfile'])
            ->where('visibility', ProfileVisibility::Public->value)
            ->when($currentProfile, fn (Builder $query) => $query->whereKeyNot($currentProfile->id))
            ->when($blockedProfileIds !== [], fn (Builder $query) => $query->whereKeyNot($blockedProfileIds))
            ->when(isset($filters['sport_id']), fn (Builder $query) => $this->filterBySportId($query, (int) $filters['sport_id']))
            ->when(isset($filters['sport_slug']), fn (Builder $query) => $this->filterBySportSlug($query, (string) $filters['sport_slug']))
            ->when(isset($filters['level']), fn (Builder $query) => $this->filterByLevel($query, (string) $filters['level']))
            ->when($this->hasAvailabilityFilter($filters), fn (Builder $query) => $this->filterByAvailabilityOverlap($query, $filters))
            ->orderBy('display_name')
            ->orderBy('id')
            ->get();

        return $profiles
            ->map(fn (SportProfile $profile) => $this->cardForProfile($profile, $currentProfile, $filters))
            ->filter(fn (array $card) => $this->passesDistanceFilter($card, $filters))
            ->sortBy([
                ['score', 'desc'],
                ['distance_km', 'asc'],
                fn (array $card) => $card['profile']->display_name,
                fn (array $card) => $card['profile']->id,
            ])
            ->take(50)
            ->values();
    }

    private function blockedProfileIds(int $profileId): array
    {
        return Connection::query()
            ->where('type', 'block')
            ->where('status', 'blocked')
            ->where(function (Builder $query) use ($profileId): void {
                $query
                    ->where('requester_profile_id', $profileId)
                    ->orWhere('target_profile_id', $profileId);
            })
            ->get(['requester_profile_id', 'target_profile_id'])
            ->map(fn (Connection $connection) => $connection->requester_profile_id === $profileId
                ? $connection->target_profile_id
                : $connection->requester_profile_id)
            ->all();
    }

    private function filterBySportId(Builder $query, int $sportId): Builder
    {
        return $query->whereHas('sports', fn (Builder $query) => $query->where('sport_id', $sportId));
    }

    private function filterBySportSlug(Builder $query, string $sportSlug): Builder
    {
        return $query->whereHas('sports.sport', fn (Builder $query) => $query->where('slug', $sportSlug));
    }

    private function filterByLevel(Builder $query, string $level): Builder
    {
        return $query->whereHas('sports', fn (Builder $query) => $query->where('level', $level));
    }

    private function cardForProfile(SportProfile $profile, ?SportProfile $currentProfile, array $filters): array
    {
        $score = 0;
        $reasons = [];

        if ($this->hasCommonSport($profile, $currentProfile)) {
            $score += 100;
            $reasons[] = 'same_sport';
        }

        if ($this->hasCompatibleLevel($profile, $currentProfile, $filters)) {
            $score += 60;
            $reasons[] = 'compatible_level';
        }

        if ($this->hasCompatibleAvailability($profile, $currentProfile, $filters)) {
            $score += 40;
            $reasons[] = 'available';
        }

        $distanceKm = $this->distanceKm($currentProfile, $profile);
        if ($distanceKm !== null) {
            $score += max(0, 30 - min(30, (int) floor($distanceKm)));
            $reasons[] = 'nearby';
        }

        $completenessScore = $this->completenessScore($profile);
        if ($completenessScore >= 12) {
            $reasons[] = 'complete_profile';
        }
        $score += $completenessScore;

        if ($profile->teacherProfile !== null) {
            $reasons[] = 'teacher';
        }

        return [
            'type' => $profile->teacherProfile === null ? 'person' : 'teacher',
            'score' => $score,
            'reasons' => array_values(array_unique($reasons)),
            'distance_km' => $distanceKm,
            'profile' => $profile,
            'teacher_profile' => $profile->teacherProfile,
        ];
    }

    private function hasAvailabilityFilter(array $filters): bool
    {
        return isset($filters['weekday'], $filters['starts_at'], $filters['ends_at']);
    }

    private function filterByAvailabilityOverlap(Builder $query, array $filters): Builder
    {
        return $query->whereHas('availabilityWindows', function (Builder $query) use ($filters): void {
            $query
                ->where('weekday', (int) $filters['weekday'])
                ->where('starts_at', '<', $filters['ends_at'])
                ->where('ends_at', '>', $filters['starts_at']);
        });
    }

    private function passesDistanceFilter(array $card, array $filters): bool
    {
        if (! isset($filters['distance_km'])) {
            return true;
        }

        return $card['distance_km'] !== null && $card['distance_km'] <= (float) $filters['distance_km'];
    }

    private function hasCommonSport(SportProfile $profile, ?SportProfile $currentProfile): bool
    {
        if ($currentProfile === null) {
            return false;
        }

        return $profile->sports
            ->pluck('sport_id')
            ->intersect($currentProfile->sports->pluck('sport_id'))
            ->isNotEmpty();
    }

    private function hasCompatibleLevel(SportProfile $profile, ?SportProfile $currentProfile, array $filters): bool
    {
        if (isset($filters['level'])) {
            return $profile->sports->contains(fn ($sport) => $sport->level?->value === $filters['level']);
        }

        if ($currentProfile === null) {
            return false;
        }

        $currentLevelsBySport = $currentProfile->sports->mapWithKeys(fn ($sport) => [
            $sport->sport_id => $sport->level?->value,
        ]);

        return $profile->sports->contains(fn ($sport) => $currentLevelsBySport->get($sport->sport_id) === $sport->level?->value);
    }

    private function hasCompatibleAvailability(SportProfile $profile, ?SportProfile $currentProfile, array $filters): bool
    {
        if ($this->hasAvailabilityFilter($filters)) {
            return $profile->availabilityWindows->contains(fn ($window) => $this->windowsOverlap(
                (int) $window->weekday,
                (string) $window->starts_at,
                (string) $window->ends_at,
                (int) $filters['weekday'],
                (string) $filters['starts_at'],
                (string) $filters['ends_at'],
            ));
        }

        if ($currentProfile === null) {
            return false;
        }

        foreach ($profile->availabilityWindows as $profileWindow) {
            foreach ($currentProfile->availabilityWindows as $currentWindow) {
                if ($this->windowsOverlap(
                    (int) $profileWindow->weekday,
                    (string) $profileWindow->starts_at,
                    (string) $profileWindow->ends_at,
                    (int) $currentWindow->weekday,
                    (string) $currentWindow->starts_at,
                    (string) $currentWindow->ends_at,
                )) {
                    return true;
                }
            }
        }

        return false;
    }

    private function windowsOverlap(int $firstWeekday, string $firstStartsAt, string $firstEndsAt, int $secondWeekday, string $secondStartsAt, string $secondEndsAt): bool
    {
        return $firstWeekday === $secondWeekday
            && $firstStartsAt < $secondEndsAt
            && $firstEndsAt > $secondStartsAt;
    }

    private function distanceKm(?SportProfile $currentProfile, SportProfile $profile): ?float
    {
        if (
            $currentProfile?->latitude_approx === null
            || $currentProfile->longitude_approx === null
            || $profile->latitude_approx === null
            || $profile->longitude_approx === null
        ) {
            return null;
        }

        $earthRadiusKm = 6371;
        $latitudeDelta = deg2rad($profile->latitude_approx - $currentProfile->latitude_approx);
        $longitudeDelta = deg2rad($profile->longitude_approx - $currentProfile->longitude_approx);
        $currentLatitude = deg2rad($currentProfile->latitude_approx);
        $profileLatitude = deg2rad($profile->latitude_approx);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($currentLatitude) * cos($profileLatitude) * sin($longitudeDelta / 2) ** 2;

        return round($earthRadiusKm * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine)), 1);
    }

    private function completenessScore(SportProfile $profile): int
    {
        $score = 0;

        foreach (['display_name', 'bio', 'city', 'region', 'avatar_url'] as $attribute) {
            if (filled($profile->{$attribute})) {
                $score += 2;
            }
        }

        if ($profile->latitude_approx !== null && $profile->longitude_approx !== null) {
            $score += 4;
        }

        if ($profile->sports->isNotEmpty()) {
            $score += 4;
        }

        if ($profile->availabilityWindows->isNotEmpty()) {
            $score += 4;
        }

        return $score;
    }
}
