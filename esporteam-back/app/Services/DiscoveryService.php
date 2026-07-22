<?php

namespace App\Services;

use App\Enums\ProfileVisibility;
use App\Enums\SessionParticipantStatus;
use App\Enums\SportSessionEntryMode;
use App\Enums\SportSessionStatus;
use App\Models\Connection;
use App\Models\ProfileSport;
use App\Models\SportProfile;
use App\Models\SportSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @wiki app/brain/services/DiscoveryService.md
 */
class DiscoveryService
{
    private const LEVEL_ORDER = [
        'beginner' => 0,
        'intermediate' => 1,
        'advanced' => 2,
        'competitive' => 3,
    ];

    /**
     * @return array{mode:string,cards:Collection<int,array<string,mixed>>,empty_state:?array<string,mixed>}
     */
    public function discoverForUser(int $userId, array $filters = []): array
    {
        $mode = $filters['mode'] ?? 'people';

        $cards = match ($mode) {
            'sessions' => $this->sessionCardsForUser($userId, $filters),
            'places' => $this->placeCardsForUser($userId, $filters),
            default => $this->profilesForUser($userId, $filters),
        };

        return [
            'mode' => $mode,
            'cards' => $cards,
            'empty_state' => $cards->isEmpty() ? $this->emptyStateFor($mode, $filters) : null,
        ];
    }

    /**
     * @wiki app/brain/functions/DiscoveryService.md#profilesForUser
     */
    public function profilesForUser(int $userId, array $filters = []): Collection
    {
        $currentProfile = $this->currentProfileForUser($userId);
        $blockedProfileIds = $currentProfile === null ? [] : $this->blockedProfileIds($currentProfile->id);

        $profiles = SportProfile::query()
            ->with(['sports.sport', 'availabilityWindows', 'teacherProfile'])
            ->where('visibility', ProfileVisibility::Public->value)
            ->when($currentProfile, fn (Builder $query) => $query->whereKeyNot($currentProfile->id))
            ->when($blockedProfileIds !== [], fn (Builder $query) => $query->whereKeyNot($blockedProfileIds))
            ->when(isset($filters['sport_id']), fn (Builder $query) => $this->filterBySportId($query, (int) $filters['sport_id']))
            ->when(isset($filters['sport_slug']), fn (Builder $query) => $this->filterBySportSlug($query, (string) $filters['sport_slug']))
            ->when(isset($filters['level']), fn (Builder $query) => $this->filterByLevel($query, (string) $filters['level']))
            ->when(isset($filters['goal']), fn (Builder $query) => $this->filterByGoal($query, (string) $filters['goal']))
            ->when($this->hasAvailabilityFilter($filters), fn (Builder $query) => $this->filterByAvailabilityOverlap($query, $filters))
            ->orderBy('display_name')
            ->orderBy('id')
            ->get();

        return $profiles
            ->map(fn (SportProfile $profile) => $this->cardForProfile($profile, $currentProfile, $filters))
            ->sortBy([
                ['score', 'desc'],
                ['distance_km', 'asc'],
                fn (array $card) => $card['profile']->display_name,
                fn (array $card) => $card['profile']->id,
            ])
            ->take(50)
            ->values();
    }

    private function sessionCardsForUser(int $userId, array $filters): Collection
    {
        $currentProfile = $this->currentProfileForUser($userId);
        $blockedProfileIds = $currentProfile === null ? [] : $this->blockedProfileIds($currentProfile->id);

        $sessions = SportSession::query()
            ->with(['creator.sports.sport', 'creator.availabilityWindows', 'participants', 'participationRecords', 'sport'])
            ->withCount(['participants as participant_count' => fn (Builder $query) => $query->whereIn('session_participants.status', SessionParticipantStatus::activeValues())])
            ->where('status', SportSessionStatus::Open->value)
            ->where('visibility', 'public')
            ->when($currentProfile, fn (Builder $query) => $query->where('creator_profile_id', '!=', $currentProfile->id))
            ->when($blockedProfileIds !== [], fn (Builder $query) => $query->whereNotIn('creator_profile_id', $blockedProfileIds))
            ->when(isset($filters['sport_id']), fn (Builder $query) => $query->where('sport_id', (int) $filters['sport_id']))
            ->when(isset($filters['sport_slug']), fn (Builder $query) => $query->whereHas('sport', fn (Builder $query) => $query->where('slug', $filters['sport_slug'])))
            ->when(isset($filters['type']), fn (Builder $query) => $query->where('type', $filters['type']))
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        return $sessions
            ->map(fn (SportSession $session) => $this->cardForSession($session, $currentProfile, $filters))
            ->filter(fn (array $card) => $this->passesSessionCompatibilityGate($card['session'], $currentProfile))
            ->filter(fn (array $card) => $this->passesSessionCapacityGate($card['session']))
            ->filter(fn (array $card) => $this->passesSessionAvailabilityFilter($card['session'], $filters))
            ->filter(fn (array $card) => $this->passesSessionHostLevelFilter($card['session'], $filters))
            ->filter(fn (array $card) => $this->passesSessionHostGoalFilter($card['session'], $filters))
            ->sortBy([
                fn (array $card) => -$card['score'],
                fn (array $card) => $card['session']->starts_at?->getTimestamp() ?? PHP_INT_MAX,
                fn (array $card) => $card['distance_km'] ?? PHP_INT_MAX,
                fn (array $card) => $card['session']->id,
            ])
            ->take(50)
            ->values();
    }

    private function placeCardsForUser(int $userId, array $filters): Collection
    {
        return $this->sessionCardsForUser($userId, $filters)
            ->groupBy(fn (array $card) => $this->placeKey($card['session']))
            ->map(fn (Collection $cards) => $this->cardForPlace($cards))
            ->sortBy([
                fn (array $card) => -$card['score'],
                fn (array $card) => $card['distance_km'] ?? PHP_INT_MAX,
                fn (array $card) => $card['place']['label'],
            ])
            ->values();
    }

    private function currentProfileForUser(int $userId): ?SportProfile
    {
        return SportProfile::query()
            ->with(['sports.sport', 'availabilityWindows'])
            ->where('user_id', $userId)
            ->first();
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

    private function filterByGoal(Builder $query, string $goal): Builder
    {
        return $query->whereHas('sports', fn (Builder $query) => $query->whereJsonContains('goals', $goal));
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

        if ($this->hasCompatibleGoal($profile, $currentProfile, $filters)) {
            $score += 50;
            $reasons[] = 'compatible_goal';
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

        $reasons = array_values(array_unique($reasons));

        return [
            'type' => $profile->teacherProfile === null ? 'person' : 'teacher',
            'score' => $score,
            'reasons' => $reasons,
            'distance_km' => $distanceKm,
            'profile' => $profile,
            'teacher_profile' => $profile->teacherProfile,
            'primary_sport' => $this->primarySportSummary($profile, $filters),
            'availability_summary' => $this->availabilitySummary($profile),
            'location_label' => $this->locationLabel($profile->city, $profile->region),
            'recommendation_reason' => $this->recommendationReason($reasons),
            'trust_signals' => $this->trustSignals($profile),
        ];
    }

    private function cardForSession(SportSession $session, ?SportProfile $currentProfile, array $filters): array
    {
        $score = 0;
        $reasons = [];

        if ($currentProfile !== null && $session->sport_id !== null && $currentProfile->sports->contains('sport_id', $session->sport_id)) {
            $score += 100;
            $reasons[] = 'same_sport';
        }

        if ($this->sessionHostHasLevel($session, $filters['level'] ?? null)) {
            $score += 60;
            $reasons[] = 'compatible_level';
        }

        if ($this->sessionHostHasGoal($session, $filters['goal'] ?? null)) {
            $score += 50;
            $reasons[] = 'compatible_goal';
        }

        if ($this->hasCompatibleSessionTime($session, $currentProfile, $filters)) {
            $score += 40;
            $reasons[] = 'available';
        }

        $distanceKm = $this->distanceKm($currentProfile, $session);
        if ($distanceKm !== null) {
            $score += max(0, 30 - min(30, (int) floor($distanceKm)));
            $reasons[] = 'nearby';
        }

        $participantCount = (int) ($session->participant_count ?? 0);
        $entryRule = match (true) {
            $session->entry_mode === SportSessionEntryMode::PublicApproval || $session->requires_approval => 'approval_required',
            $session->entry_mode === SportSessionEntryMode::InviteOnly => 'invite_only',
            default => 'match_required',
        };

        $reasons = array_values(array_unique($reasons));

        return [
            'type' => 'session',
            'score' => $score,
            'reasons' => $reasons,
            'distance_km' => $distanceKm,
            'session' => $session,
            'host' => $session->creator,
            'entry_rule' => $entryRule,
            'participant_count' => $participantCount,
            'participation_status' => $this->participationStatusFor($session, $currentProfile),
            'recommendation_reason' => $this->recommendationReason($reasons),
        ];
    }

    private function cardForPlace(Collection $cards): array
    {
        $sortedCards = $cards->sortBy(fn (array $card) => $card['session']->starts_at?->getTimestamp() ?? PHP_INT_MAX)->values();
        $firstCard = $sortedCards->first();
        $session = $firstCard['session'];
        $distances = $cards->pluck('distance_km')->filter(fn (?float $distance) => $distance !== null);
        $reasons = $cards
            ->flatMap(fn (array $card) => $card['reasons'])
            ->merge(['open_sessions'])
            ->unique()
            ->values()
            ->all();

        return [
            'type' => 'place',
            'score' => (int) $cards->max('score') + ($cards->count() * 10),
            'reasons' => $reasons,
            'distance_km' => $distances->isEmpty() ? null : (float) $distances->min(),
            'place' => [
                'label' => $session->location_label ?: $this->locationLabel($session->city, $session->region) ?: 'Local a definir',
                'city' => $session->city,
                'region' => $session->region,
                'location_label_public' => $session->location_label ?: $this->locationLabel($session->city, $session->region),
                'sports' => $cards
                    ->pluck('session.sport')
                    ->filter()
                    ->unique('id')
                    ->map(fn ($sport) => [
                        'id' => $sport->id,
                        'name' => $sport->name,
                        'slug' => $sport->slug,
                        'category' => $sport->category,
                        'is_active' => $sport->is_active,
                    ])
                    ->values()
                    ->all(),
                'open_session_count' => $cards->count(),
                'next_session_starts_at' => $session->starts_at?->toISOString(),
            ],
            'recommendation_reason' => $this->recommendationReason($reasons),
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

    private function passesSessionCapacityGate(SportSession $session): bool
    {
        if ($session->capacity === null) {
            return true;
        }

        return (int) ($session->participant_count ?? 0) < $session->capacity;
    }

    private function passesSessionCompatibilityGate(SportSession $session, ?SportProfile $profile): bool
    {
        if ($profile === null) {
            return true;
        }

        if ($session->sport_id !== null && ! $profile->sports->contains('sport_id', $session->sport_id)) {
            return false;
        }

        if (($session->min_level !== null || $session->max_level !== null)
            && ! $profile->sports->contains(function (ProfileSport $practice) use ($session): bool {
                if ($session->sport_id !== null && $practice->sport_id !== $session->sport_id) {
                    return false;
                }

                $level = $practice->level?->value;
                if ($level === null) {
                    return false;
                }

                $order = self::LEVEL_ORDER[$level];

                return ($session->min_level === null || $order >= self::LEVEL_ORDER[$session->min_level])
                    && ($session->max_level === null || $order <= self::LEVEL_ORDER[$session->max_level]);
            })) {
            return false;
        }

        return $profile->availabilityWindows->isEmpty()
            || $this->hasCompatibleSessionTime($session, $profile, []);
    }

    private function participationStatusFor(SportSession $session, ?SportProfile $profile): ?string
    {
        if ($profile === null || ! $session->relationLoaded('participationRecords')) {
            return null;
        }

        return $session->participationRecords
            ->firstWhere('sport_profile_id', $profile->id)?->status?->value;
    }

    private function passesSessionAvailabilityFilter(SportSession $session, array $filters): bool
    {
        if (! $this->hasAvailabilityFilter($filters)) {
            return true;
        }

        return $this->sessionStartsWithinWindow($session, $filters);
    }

    private function passesSessionHostLevelFilter(SportSession $session, array $filters): bool
    {
        if (! isset($filters['level'])) {
            return true;
        }

        return $this->sessionHostHasLevel($session, (string) $filters['level']);
    }

    private function passesSessionHostGoalFilter(SportSession $session, array $filters): bool
    {
        if (! isset($filters['goal'])) {
            return true;
        }

        return $this->sessionHostHasGoal($session, (string) $filters['goal']);
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

    private function hasCompatibleGoal(SportProfile $profile, ?SportProfile $currentProfile, array $filters): bool
    {
        if (isset($filters['goal'])) {
            return $profile->sports->contains(fn (ProfileSport $sport) => in_array($filters['goal'], $sport->goals ?? [], true));
        }

        if ($currentProfile === null) {
            return false;
        }

        $currentGoalsBySport = $currentProfile->sports->mapWithKeys(fn (ProfileSport $sport) => [
            $sport->sport_id => $sport->goals ?? [],
        ]);

        return $profile->sports->contains(function (ProfileSport $sport) use ($currentGoalsBySport): bool {
            $currentGoals = $currentGoalsBySport->get($sport->sport_id, []);

            return count(array_intersect($currentGoals, $sport->goals ?? [])) > 0;
        });
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

    private function hasCompatibleSessionTime(SportSession $session, ?SportProfile $currentProfile, array $filters): bool
    {
        if ($this->hasAvailabilityFilter($filters)) {
            return $this->sessionStartsWithinWindow($session, $filters);
        }

        if ($currentProfile === null) {
            return false;
        }

        return $currentProfile->availabilityWindows->contains(fn ($window) => $this->sessionStartsWithinWindow($session, [
            'weekday' => $window->weekday,
            'starts_at' => (string) $window->starts_at,
            'ends_at' => (string) $window->ends_at,
        ]));
    }

    private function sessionStartsWithinWindow(SportSession $session, array $filters): bool
    {
        if ($session->starts_at === null) {
            return false;
        }

        $startsAt = $session->starts_at;
        $sessionTime = $startsAt->format('H:i');

        return (int) $startsAt->dayOfWeek === (int) $filters['weekday']
            && $sessionTime >= (string) $filters['starts_at']
            && $sessionTime < (string) $filters['ends_at'];
    }

    private function sessionHostHasLevel(SportSession $session, ?string $level): bool
    {
        if ($level === null || $session->creator === null) {
            return false;
        }

        return $session->creator->sports->contains(fn (ProfileSport $sport) => $sport->level?->value === $level
            && ($session->sport_id === null || $sport->sport_id === $session->sport_id));
    }

    private function sessionHostHasGoal(SportSession $session, ?string $goal): bool
    {
        if ($goal === null || $session->creator === null) {
            return false;
        }

        return $session->creator->sports->contains(fn (ProfileSport $sport) => in_array($goal, $sport->goals ?? [], true)
            && ($session->sport_id === null || $sport->sport_id === $session->sport_id));
    }

    private function windowsOverlap(int $firstWeekday, string $firstStartsAt, string $firstEndsAt, int $secondWeekday, string $secondStartsAt, string $secondEndsAt): bool
    {
        return $firstWeekday === $secondWeekday
            && $firstStartsAt < $secondEndsAt
            && $firstEndsAt > $secondStartsAt;
    }

    private function distanceKm(?SportProfile $currentProfile, SportProfile|SportSession $candidate): ?float
    {
        if (
            $currentProfile?->latitude_approx === null
            || $currentProfile->longitude_approx === null
            || $candidate->latitude_approx === null
            || $candidate->longitude_approx === null
        ) {
            return null;
        }

        $earthRadiusKm = 6371;
        $latitudeDelta = deg2rad($candidate->latitude_approx - $currentProfile->latitude_approx);
        $longitudeDelta = deg2rad($candidate->longitude_approx - $currentProfile->longitude_approx);
        $currentLatitude = deg2rad($currentProfile->latitude_approx);
        $candidateLatitude = deg2rad($candidate->latitude_approx);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($currentLatitude) * cos($candidateLatitude) * sin($longitudeDelta / 2) ** 2;

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

    private function primarySportSummary(SportProfile $profile, array $filters): ?array
    {
        $practices = $profile->sports;

        if (isset($filters['sport_slug'])) {
            $filtered = $practices->filter(fn (ProfileSport $sport) => $sport->sport?->slug === $filters['sport_slug']);
            $practices = $filtered->isNotEmpty() ? $filtered : $practices;
        }

        if (isset($filters['sport_id'])) {
            $filtered = $practices->filter(fn (ProfileSport $sport) => $sport->sport_id === (int) $filters['sport_id']);
            $practices = $filtered->isNotEmpty() ? $filtered : $practices;
        }

        $practice = $practices->firstWhere('is_primary', true) ?? $practices->first();

        if ($practice === null) {
            return null;
        }

        return [
            'sport' => $practice->sport === null ? null : [
                'id' => $practice->sport->id,
                'name' => $practice->sport->name,
                'slug' => $practice->sport->slug,
            ],
            'level' => $practice->level?->value,
            'goals' => $practice->goals ?? [],
        ];
    }

    private function availabilitySummary(SportProfile $profile): array
    {
        $windows = $profile->availabilityWindows
            ->sortBy([['weekday', 'asc'], ['starts_at', 'asc']])
            ->take(2)
            ->map(fn ($window) => [
                'weekday' => $window->weekday,
                'starts_at' => (string) $window->starts_at,
                'ends_at' => (string) $window->ends_at,
            ])
            ->values()
            ->all();

        return [
            'window_count' => $profile->availabilityWindows->count(),
            'windows' => $windows,
        ];
    }

    private function trustSignals(SportProfile $profile): array
    {
        return [
            'profile_complete' => $this->completenessScore($profile) >= 12,
            'has_avatar' => filled($profile->avatar_url),
            'has_bio' => filled($profile->bio),
            'has_public_location' => filled($profile->city) || filled($profile->region),
            'has_sports' => $profile->sports->isNotEmpty(),
            'has_availability' => $profile->availabilityWindows->isNotEmpty(),
            'teacher_verified' => $profile->teacherProfile?->verified_at !== null,
        ];
    }

    private function locationLabel(?string $city, ?string $region): ?string
    {
        return collect([$city, $region])
            ->filter()
            ->implode(', ') ?: null;
    }

    private function recommendationReason(array $reasons): ?string
    {
        return $reasons[0] ?? null;
    }

    private function placeKey(SportSession $session): string
    {
        return implode('|', [
            $session->location_label ?? '',
            $session->city ?? '',
            $session->region ?? '',
            $session->latitude_approx ?? '',
            $session->longitude_approx ?? '',
        ]);
    }

    private function emptyStateFor(string $mode, array $filters): array
    {
        $suggestions = [];

        if (isset($filters['level'])) {
            $suggestions[] = [
                'action' => 'remove_level_filter',
                'label' => 'Remover filtro de nivel',
                'params' => ['level' => null],
            ];
        }

        $suggestions[] = [
            'action' => 'create_public_session',
            'label' => 'Criar sessao publica',
            'params' => ['mode' => 'sessions'],
        ];

        if (isset($filters['goal'])) {
            $suggestions[] = [
                'action' => 'remove_goal_filter',
                'label' => 'Remover filtro de objetivo',
                'params' => ['goal' => null],
            ];
        }

        if ($this->hasAvailabilityFilter($filters)) {
            $suggestions[] = [
                'action' => 'relax_availability',
                'label' => 'Relaxar disponibilidade',
                'params' => ['weekday' => null, 'starts_at' => null, 'ends_at' => null],
            ];
        }

        if (isset($filters['sport_id']) || isset($filters['sport_slug'])) {
            $suggestions[] = [
                'action' => 'remove_sport_filter',
                'label' => 'Ver outras modalidades',
                'params' => ['sport_id' => null, 'sport_slug' => null],
            ];
        }

        return [
            'title' => match ($mode) {
                'sessions' => 'Nenhuma sessao encontrada',
                'places' => 'Nenhum local encontrado',
                default => 'Nenhum perfil encontrado',
            },
            'message' => 'Ajuste os filtros ou crie uma sessao publica para atrair perfis esportivos compativeis.',
            'suggestions' => $suggestions,
        ];
    }
}
