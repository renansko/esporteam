<?php

namespace App\Services;

use App\Enums\ProfileVisibility;
use App\Enums\SessionParticipantStatus;
use App\Enums\SportSessionEntryMode;
use App\Enums\SportSessionStatus;
use App\Models\Connection;
use App\Models\ProfileSport;
use App\Models\SessionParticipant;
use App\Models\SportProfile;
use App\Models\SportSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @wiki app/brain/services/SportSessionService.md
 */
class SportSessionService
{
    private const LEVEL_ORDER = [
        'beginner' => 0,
        'intermediate' => 1,
        'advanced' => 2,
        'competitive' => 3,
    ];

    /**
     * @wiki app/brain/functions/SportSessionService.md#createForUser
     */
    public function createForUser(int $userId, array $data): SportSession
    {
        $profile = $this->requireProfile($userId);
        $entryMode = $this->entryModeFromInput($data);
        $this->assertValidLevelRange($data['min_level'] ?? null, $data['max_level'] ?? null);

        return DB::transaction(function () use ($profile, $data, $entryMode) {
            $session = SportSession::query()->create([
                'creator_profile_id' => $profile->id,
                'sport_id' => $data['sport_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'],
                'starts_at' => $data['starts_at'],
                'location_label' => $data['location_label'] ?? null,
                'city' => $data['city'] ?? null,
                'region' => $data['region'] ?? null,
                'latitude_approx' => $data['latitude_approx'] ?? null,
                'longitude_approx' => $data['longitude_approx'] ?? null,
                'capacity' => $data['capacity'] ?? null,
                'requires_approval' => $entryMode->requiresApproval(),
                'entry_mode' => $entryMode->value,
                'min_level' => $data['min_level'] ?? null,
                'max_level' => $data['max_level'] ?? null,
                'visibility' => $data['visibility'] ?? 'public',
                'status' => $data['status'] ?? SportSessionStatus::Open->value,
            ]);

            $session->participants()->attach($profile->id, [
                'status' => SessionParticipantStatus::Joined->value,
            ]);

            return $this->freshSession($session);
        });
    }

    /**
     * @wiki app/brain/functions/SportSessionService.md#openSessions
     */
    public function openSessions(int $userId, array $filters = []): Collection
    {
        $currentProfile = $this->profileForUser($userId);

        $sessions = SportSession::query()
            ->with(['creator', 'sport'])
            ->withCount(['participants as participant_count' => fn (Builder $query) => $query->whereIn('session_participants.status', SessionParticipantStatus::activeValues())])
            ->where('status', SportSessionStatus::Open->value)
            ->where('visibility', 'public')
            ->when(isset($filters['sport_id']), fn (Builder $query) => $query->where('sport_id', (int) $filters['sport_id']))
            ->when(isset($filters['sport_slug']), fn (Builder $query) => $query->whereHas('sport', fn (Builder $query) => $query->where('slug', $filters['sport_slug'])))
            ->when(isset($filters['type']), fn (Builder $query) => $query->where('type', $filters['type']))
            ->when(isset($filters['entry_mode']), fn (Builder $query) => $query->where('entry_mode', $filters['entry_mode']))
            ->when(isset($filters['city']), fn (Builder $query) => $query->where('city', $filters['city']))
            ->when(isset($filters['region']), fn (Builder $query) => $query->where('region', $filters['region']))
            ->when(isset($filters['starts_after']), fn (Builder $query) => $query->where('starts_at', '>=', $filters['starts_after']))
            ->when(isset($filters['starts_before']), fn (Builder $query) => $query->where('starts_at', '<=', $filters['starts_before']))
            ->when(
                $this->hasViewportBounds($filters),
                fn (Builder $query) => $query
                    ->whereBetween('latitude_approx', [(float) $filters['south'], (float) $filters['north']])
                    ->whereBetween('longitude_approx', [(float) $filters['west'], (float) $filters['east']]),
            )
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        return $sessions
            ->filter(fn (SportSession $session) => $this->passesSessionLevelFilter($session, $filters['level'] ?? null))
            ->filter(fn (SportSession $session) => $this->passesSessionDistanceFilter($session, $currentProfile, $filters))
            ->filter(fn (SportSession $session) => $this->passesSessionTimeWindowFilter($session, $filters))
            ->filter(fn (SportSession $session) => $this->passesAvailableSlotsFilter($session, $filters))
            ->map(fn (SportSession $session) => $this->withPublicSessionState($session, $currentProfile))
            ->sortBy([
                fn (SportSession $session) => $session->starts_at?->getTimestamp() ?? PHP_INT_MAX,
                fn (SportSession $session) => $session->id,
            ])
            ->take(50)
            ->values();
    }

    private function hasViewportBounds(array $filters): bool
    {
        return collect(['south', 'north', 'west', 'east'])
            ->every(fn (string $key) => array_key_exists($key, $filters));
    }

    /**
     * Returns one public session detail without exposing private sessions or
     * sessions hosted by a blocked profile.
     */
    public function detailForUser(int $userId, SportSession $session): SportSession
    {
        $profile = $this->profileForUser($userId);

        if (
            $session->status !== SportSessionStatus::Open
            || $session->visibility !== 'public'
            || ($profile !== null && $session->creator_profile_id !== $profile->id
                && $this->profilesAreBlocked($session->creator_profile_id, $profile->id))
        ) {
            abort(404, 'Session not found.');
        }

        $session = SportSession::query()
            ->whereKey($session->id)
            ->with(['creator', 'sport', 'participants'])
            ->withCount(['participants as participant_count' => fn (Builder $query) => $query->whereIn('session_participants.status', SessionParticipantStatus::activeValues())])
            ->when(
                $profile !== null,
                fn (Builder $query) => $query->with(['participationRecords' => fn ($query) => $query
                    ->where('sport_profile_id', $profile->id)
                    ->with('profile')]),
            )
            ->firstOrFail();

        return $this->withPublicSessionState($session, $profile);
    }

    /**
     * @wiki app/brain/functions/SportSessionService.md#participantSessionsForUser
     */
    public function participantSessionsForUser(int $userId): Collection
    {
        $profile = $this->requireProfile($userId);

        return SportSession::query()
            ->with(['creator', 'sport', 'participants'])
            ->with(['participationRecords' => fn ($query) => $query
                ->where('sport_profile_id', $profile->id)
                ->with('profile')])
            ->withCount(['participants as participant_count' => fn (Builder $query) => $query->whereIn('session_participants.status', SessionParticipantStatus::activeValues())])
            ->whereHas('participationRecords', fn (Builder $query) => $query->where('sport_profile_id', $profile->id))
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    /**
     * @wiki app/brain/functions/SportSessionService.md#recommendationsForHost
     */
    public function recommendationsForHost(int $userId, SportSession $session, array $filters = []): Collection
    {
        $host = $this->authorizeHost($userId, $session);
        $blockedProfileIds = $this->blockedProfileIds($host->id);
        $existingParticipantIds = DB::table('session_participants')
            ->where('sport_session_id', $session->id)
            ->pluck('sport_profile_id')
            ->all();

        $profiles = SportProfile::query()
            ->with(['sports.sport', 'availabilityWindows'])
            ->where('visibility', ProfileVisibility::Public->value)
            ->whereKeyNot($host->id)
            ->whereNotIn('id', $existingParticipantIds)
            ->when($blockedProfileIds !== [], fn (Builder $query) => $query->whereNotIn('id', $blockedProfileIds))
            ->when($session->sport_id !== null, fn (Builder $query) => $query->whereHas('sports', fn (Builder $query) => $query->where('sport_id', $session->sport_id)))
            ->when(isset($filters['level']), fn (Builder $query) => $query->whereHas('sports', fn (Builder $query) => $query->where('level', $filters['level'])))
            ->when(isset($filters['goal']), fn (Builder $query) => $query->whereHas('sports', fn (Builder $query) => $query->whereJsonContains('goals', $filters['goal'])))
            ->whereHas('availabilityWindows', fn (Builder $query) => $this->filterAvailabilityForSession($query, $session))
            ->orderBy('display_name')
            ->orderBy('id')
            ->get();

        return $profiles
            ->map(fn (SportProfile $profile) => $this->recommendationCard($profile, $session, $filters))
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

    /**
     * @wiki app/brain/functions/SportSessionService.md#inviteProfiles
     */
    public function inviteProfiles(int $userId, SportSession $session, array $profileIds): SportSession
    {
        $host = $this->authorizeHost($userId, $session);

        return DB::transaction(function () use ($session, $profileIds, $host) {
            $lockedSession = $this->lockOpenSession($session);
            $profiles = SportProfile::query()
                ->whereIn('id', $profileIds)
                ->get()
                ->keyBy('id');

            $missingProfileIds = array_diff($profileIds, $profiles->keys()->all());
            if ($missingProfileIds !== []) {
                throw ValidationException::withMessages([
                    'profile_ids' => 'One or more sport profiles were not found.',
                ]);
            }

            foreach ($profileIds as $profileId) {
                /** @var SportProfile $profile */
                $profile = $profiles->get($profileId);
                $this->assertProfileCanParticipate($lockedSession, $host, $profile, 'profile_ids');
                $this->assertProfileMatchesLevelRange($lockedSession, $profile, 'profile_ids');
            }

            $availableSlots = $this->availableReservedSlots($lockedSession);
            if ($lockedSession->capacity !== null && count($profileIds) > $availableSlots) {
                throw ValidationException::withMessages([
                    'capacity' => 'Session capacity does not allow these invites.',
                ]);
            }

            foreach ($profileIds as $profileId) {
                $this->upsertParticipation($lockedSession->id, $profileId, SessionParticipantStatus::Invited);
            }

            return $this->freshSession($lockedSession);
        });
    }

    /**
     * @wiki app/brain/functions/SportSessionService.md#respondToInvite
     */
    public function respondToInvite(int $userId, SportSession $session, string $action): SportSession
    {
        $profile = $this->requireProfile($userId);

        return DB::transaction(function () use ($session, $profile, $action) {
            $lockedSession = $this->lockOpenSession($session);
            $participation = $this->participationFor($lockedSession, $profile);

            if ($participation?->status !== SessionParticipantStatus::Invited) {
                throw ValidationException::withMessages([
                    'invite' => 'Sport profile does not have a pending session invite.',
                ]);
            }

            if ($action === 'decline') {
                $this->updateParticipationStatus($participation, SessionParticipantStatus::Declined);

                return $this->freshSession($lockedSession);
            }

            $host = $lockedSession->creator()->firstOrFail();
            $this->assertProfileCanParticipate($lockedSession, $host, $profile, 'profile');
            $this->assertProfileMatchesLevelRange($lockedSession, $profile, 'profile');
            $this->assertActiveCapacityAvailable($lockedSession);
            $this->updateParticipationStatus($participation, SessionParticipantStatus::Approved);

            return $this->freshSession($lockedSession);
        });
    }

    /**
     * @wiki app/brain/functions/SportSessionService.md#decideParticipant
     */
    public function decideParticipant(int $userId, SportSession $session, SportProfile $profile, string $action): SportSession
    {
        $host = $this->authorizeHost($userId, $session);

        return DB::transaction(function () use ($session, $profile, $action, $host) {
            $lockedSession = $this->lockOpenSession($session);
            $participation = $this->participationFor($lockedSession, $profile);

            if ($participation === null || $profile->id === $lockedSession->creator_profile_id) {
                throw ValidationException::withMessages([
                    'profile' => 'Sport profile is not part of this session workflow.',
                ]);
            }

            if ($action === 'decline') {
                $this->updateParticipationStatus($participation, SessionParticipantStatus::Declined);

                return $this->freshSession($lockedSession);
            }

            if ($action === 'remove') {
                $this->updateParticipationStatus($participation, SessionParticipantStatus::Removed);

                return $this->freshSession($lockedSession);
            }

            $this->assertProfileCanParticipate($lockedSession, $host, $profile, 'profile');
            $this->assertProfileMatchesLevelRange($lockedSession, $profile, 'profile');
            $this->assertActiveCapacityAvailable($lockedSession);
            $this->updateParticipationStatus($participation, SessionParticipantStatus::Approved);

            return $this->freshSession($lockedSession);
        });
    }

    /**
     * @wiki app/brain/functions/SportSessionService.md#join
     */
    public function join(int $userId, SportSession $session): SportSession
    {
        $profile = $this->requireProfile($userId);

        return DB::transaction(function () use ($profile, $session) {
            $lockedSession = $this->lockOpenSession($session);
            $host = $lockedSession->creator()->firstOrFail();

            if ($lockedSession->visibility !== 'public' || $lockedSession->entry_mode === SportSessionEntryMode::InviteOnly) {
                throw ValidationException::withMessages([
                    'entry_mode' => 'Session does not allow public entry.',
                ]);
            }

            $this->assertProfileCanParticipate($lockedSession, $host, $profile, 'profile');
            $this->assertProfileMatchesLevelRange($lockedSession, $profile, 'profile');

            $existing = $this->participationFor($lockedSession, $profile);
            if ($existing !== null && in_array($existing->status, [SessionParticipantStatus::Joined, SessionParticipantStatus::Approved, SessionParticipantStatus::Interested], true)) {
                throw ValidationException::withMessages([
                    'profile' => 'Sport profile already joined or requested this session.',
                ]);
            }

            if ($lockedSession->entry_mode === SportSessionEntryMode::PublicApproval) {
                $this->upsertParticipation($lockedSession->id, $profile->id, SessionParticipantStatus::Interested);

                return $this->freshSession($lockedSession);
            }

            $this->assertActiveCapacityAvailable($lockedSession);
            $this->upsertParticipation($lockedSession->id, $profile->id, SessionParticipantStatus::Joined);

            return $this->freshSession($lockedSession);
        });
    }

    private function authorizeHost(int $userId, SportSession $session): SportProfile
    {
        $profile = $this->requireProfile($userId);

        if ($session->creator_profile_id !== $profile->id) {
            abort(403, 'Only the session host can manage group match.');
        }

        return $profile;
    }

    private function lockOpenSession(SportSession $session): SportSession
    {
        $lockedSession = SportSession::query()
            ->whereKey($session->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($lockedSession->status !== SportSessionStatus::Open) {
            throw ValidationException::withMessages([
                'session' => 'Session is not open for participation.',
            ]);
        }

        return $lockedSession;
    }

    private function entryModeFromInput(array $data): SportSessionEntryMode
    {
        if (isset($data['entry_mode'])) {
            return SportSessionEntryMode::from($data['entry_mode']);
        }

        if (($data['requires_approval'] ?? false) === true) {
            return SportSessionEntryMode::PublicApproval;
        }

        return SportSessionEntryMode::PublicDirect;
    }

    private function withPublicSessionState(SportSession $session, ?SportProfile $profile): SportSession
    {
        $session->setAttribute('distance_km', $profile === null ? null : $this->distanceFromSession($session, $profile));
        $session->setAttribute('next_action', $this->nextActionForProfile($session, $profile));

        return $session;
    }

    private function nextActionForProfile(SportSession $session, ?SportProfile $profile): string
    {
        if ($profile === null) {
            return 'indisponivel';
        }

        if (
            $session->status !== SportSessionStatus::Open
            || $session->visibility !== 'public'
            || $session->creator_profile_id === $profile->id
            || $session->entry_mode === SportSessionEntryMode::InviteOnly
            || $this->participationFor($session, $profile) !== null
            || $profile->visibility !== ProfileVisibility::Public
            || $this->profilesAreBlocked($session->creator_profile_id, $profile->id)
            || ! $this->profileMatchesLevelRange($session, $profile)
        ) {
            return 'indisponivel';
        }

        if ($session->entry_mode === SportSessionEntryMode::PublicDirect && ! $this->sessionHasAvailableActiveSlot($session)) {
            return 'indisponivel';
        }

        return $session->entry_mode?->nextAction() ?? SportSessionEntryMode::PublicDirect->nextAction();
    }

    private function passesSessionLevelFilter(SportSession $session, ?string $level): bool
    {
        if ($level === null) {
            return true;
        }

        return $this->levelIsInRange($level, $session->min_level, $session->max_level);
    }

    private function passesSessionDistanceFilter(SportSession $session, ?SportProfile $profile, array $filters): bool
    {
        if (! isset($filters['distance_km'])) {
            return true;
        }

        if ($profile === null) {
            return false;
        }

        $distanceKm = $this->distanceFromSession($session, $profile);

        return $distanceKm !== null && $distanceKm <= (float) $filters['distance_km'];
    }

    private function passesSessionTimeWindowFilter(SportSession $session, array $filters): bool
    {
        if (! $this->hasTimeWindowFilter($filters)) {
            return true;
        }

        $startsAt = $session->starts_at;
        $sessionTime = $startsAt->format('H:i');

        return (int) $startsAt->dayOfWeek === (int) $filters['weekday']
            && $sessionTime >= (string) $filters['starts_at']
            && $sessionTime < (string) $filters['ends_at'];
    }

    private function hasTimeWindowFilter(array $filters): bool
    {
        return isset($filters['weekday'], $filters['starts_at'], $filters['ends_at']);
    }

    private function passesAvailableSlotsFilter(SportSession $session, array $filters): bool
    {
        if (! array_key_exists('has_available_slots', $filters) || $filters['has_available_slots'] === null) {
            return true;
        }

        $hasAvailableSlot = $this->sessionHasAvailableActiveSlot($session);

        return filter_var($filters['has_available_slots'], FILTER_VALIDATE_BOOLEAN)
            ? $hasAvailableSlot
            : ! $hasAvailableSlot;
    }

    private function sessionHasAvailableActiveSlot(SportSession $session): bool
    {
        if ($session->capacity === null) {
            return true;
        }

        return (int) ($session->participant_count ?? $this->activeParticipantCount($session->id)) < $session->capacity;
    }

    private function assertValidLevelRange(?string $minLevel, ?string $maxLevel): void
    {
        if ($minLevel === null || $maxLevel === null) {
            return;
        }

        if (self::LEVEL_ORDER[$minLevel] > self::LEVEL_ORDER[$maxLevel]) {
            throw ValidationException::withMessages([
                'min_level' => 'Minimum level must be less than or equal to maximum level.',
            ]);
        }
    }

    private function assertProfileCanParticipate(SportSession $session, SportProfile $host, SportProfile $profile, string $errorKey): void
    {
        if ($profile->id === $host->id) {
            throw ValidationException::withMessages([
                $errorKey => 'Session host is already participating.',
            ]);
        }

        if ($profile->visibility !== ProfileVisibility::Public) {
            throw ValidationException::withMessages([
                $errorKey => 'Sport profile is not visible for session participation.',
            ]);
        }

        if ($this->profilesAreBlocked($host->id, $profile->id)) {
            throw ValidationException::withMessages([
                $errorKey => 'Blocked sport profiles cannot participate in this session.',
            ]);
        }
    }

    private function assertProfileMatchesLevelRange(SportSession $session, SportProfile $profile, string $errorKey): void
    {
        if (! $this->profileMatchesLevelRange($session, $profile)) {
            throw ValidationException::withMessages([
                $errorKey => 'Sport profile level does not match this session.',
            ]);
        }
    }

    private function profileMatchesLevelRange(SportSession $session, SportProfile $profile): bool
    {
        if ($session->min_level === null && $session->max_level === null) {
            return true;
        }

        $practices = $profile->relationLoaded('sports')
            ? $profile->sports
            : $profile->sports()->get();

        if ($session->sport_id !== null) {
            $practices = $practices->where('sport_id', $session->sport_id);
        }

        return $practices->contains(fn (ProfileSport $sport) => $sport->level !== null
            && $this->levelIsInRange($sport->level->value, $session->min_level, $session->max_level));
    }

    private function levelIsInRange(string $level, ?string $minLevel, ?string $maxLevel): bool
    {
        $levelOrder = self::LEVEL_ORDER[$level];

        if ($minLevel !== null && $levelOrder < self::LEVEL_ORDER[$minLevel]) {
            return false;
        }

        if ($maxLevel !== null && $levelOrder > self::LEVEL_ORDER[$maxLevel]) {
            return false;
        }

        return true;
    }

    private function assertActiveCapacityAvailable(SportSession $session): void
    {
        if ($session->capacity === null) {
            return;
        }

        if ($this->activeParticipantCount($session->id) >= $session->capacity) {
            throw ValidationException::withMessages([
                'capacity' => 'Session capacity is full.',
            ]);
        }
    }

    private function availableReservedSlots(SportSession $session): int
    {
        if ($session->capacity === null) {
            return PHP_INT_MAX;
        }

        return max(0, $session->capacity - $this->reservedParticipantCount($session->id));
    }

    private function activeParticipantCount(int $sessionId): int
    {
        return DB::table('session_participants')
            ->where('sport_session_id', $sessionId)
            ->whereIn('status', SessionParticipantStatus::activeValues())
            ->count();
    }

    private function reservedParticipantCount(int $sessionId): int
    {
        return DB::table('session_participants')
            ->where('sport_session_id', $sessionId)
            ->whereIn('status', SessionParticipantStatus::reservedValues())
            ->count();
    }

    private function participationFor(SportSession $session, SportProfile $profile): ?SessionParticipant
    {
        return SessionParticipant::query()
            ->where('sport_session_id', $session->id)
            ->where('sport_profile_id', $profile->id)
            ->first();
    }

    private function upsertParticipation(int $sessionId, int $profileId, SessionParticipantStatus $status): void
    {
        $existing = DB::table('session_participants')
            ->where('sport_session_id', $sessionId)
            ->where('sport_profile_id', $profileId)
            ->first();

        if ($existing) {
            DB::table('session_participants')
                ->where('id', $existing->id)
                ->update([
                    'status' => $status->value,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('session_participants')->insert([
            'sport_session_id' => $sessionId,
            'sport_profile_id' => $profileId,
            'status' => $status->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function updateParticipationStatus(SessionParticipant $participation, SessionParticipantStatus $status): void
    {
        $participation->forceFill(['status' => $status])->save();
    }

    private function recommendationCard(SportProfile $profile, SportSession $session, array $filters): array
    {
        $score = 0;
        $reasons = [];

        if ($session->sport_id !== null && $profile->sports->contains('sport_id', $session->sport_id)) {
            $score += 100;
            $reasons[] = 'same_sport';
        }

        if (isset($filters['level']) && $this->profileHasLevel($profile, (string) $filters['level'], $session->sport_id)) {
            $score += 60;
            $reasons[] = 'compatible_level';
        }

        if (isset($filters['goal']) && $this->profileHasGoal($profile, (string) $filters['goal'], $session->sport_id)) {
            $score += 50;
            $reasons[] = 'compatible_goal';
        }

        if ($this->profileAvailableForSession($profile, $session)) {
            $score += 40;
            $reasons[] = 'available';
        }

        $distanceKm = $this->distanceFromSession($session, $profile);
        if ($distanceKm !== null) {
            $score += max(0, 30 - min(30, (int) floor($distanceKm)));
            $reasons[] = 'nearby';
        }

        return [
            'score' => $score,
            'reasons' => array_values(array_unique($reasons)),
            'distance_km' => $distanceKm,
            'profile' => $profile,
        ];
    }

    private function profileHasLevel(SportProfile $profile, string $level, ?int $sportId): bool
    {
        return $profile->sports->contains(fn (ProfileSport $sport) => $sport->level?->value === $level
            && ($sportId === null || $sport->sport_id === $sportId));
    }

    private function profileHasGoal(SportProfile $profile, string $goal, ?int $sportId): bool
    {
        return $profile->sports->contains(fn (ProfileSport $sport) => in_array($goal, $sport->goals ?? [], true)
            && ($sportId === null || $sport->sport_id === $sportId));
    }

    private function filterAvailabilityForSession(Builder $query, SportSession $session): Builder
    {
        $startsAt = $session->starts_at;

        return $query
            ->where('weekday', $startsAt->dayOfWeek)
            ->where('starts_at', '<=', $startsAt->format('H:i'))
            ->where('ends_at', '>', $startsAt->format('H:i'));
    }

    private function profileAvailableForSession(SportProfile $profile, SportSession $session): bool
    {
        return $profile->availabilityWindows->contains(function ($window) use ($session): bool {
            $startsAt = $session->starts_at;

            return (int) $window->weekday === $startsAt->dayOfWeek
                && (string) $window->starts_at <= $startsAt->format('H:i')
                && (string) $window->ends_at > $startsAt->format('H:i');
        });
    }

    private function passesDistanceFilter(array $card, array $filters): bool
    {
        if (! isset($filters['distance_km'])) {
            return true;
        }

        return $card['distance_km'] !== null && $card['distance_km'] <= (float) $filters['distance_km'];
    }

    private function distanceFromSession(SportSession $session, SportProfile $profile): ?float
    {
        if (
            $session->latitude_approx === null
            || $session->longitude_approx === null
            || $profile->latitude_approx === null
            || $profile->longitude_approx === null
        ) {
            return null;
        }

        $earthRadiusKm = 6371;
        $latitudeDelta = deg2rad($profile->latitude_approx - $session->latitude_approx);
        $longitudeDelta = deg2rad($profile->longitude_approx - $session->longitude_approx);
        $sessionLatitude = deg2rad($session->latitude_approx);
        $profileLatitude = deg2rad($profile->latitude_approx);

        $haversine = sin($latitudeDelta / 2) ** 2
            + cos($sessionLatitude) * cos($profileLatitude) * sin($longitudeDelta / 2) ** 2;

        return round($earthRadiusKm * 2 * atan2(sqrt($haversine), sqrt(1 - $haversine)), 1);
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

    private function profilesAreBlocked(int $firstProfileId, int $secondProfileId): bool
    {
        return Connection::query()
            ->where('type', 'block')
            ->where('status', 'blocked')
            ->where(function (Builder $query) use ($firstProfileId, $secondProfileId): void {
                $query
                    ->where(function (Builder $query) use ($firstProfileId, $secondProfileId): void {
                        $query
                            ->where('requester_profile_id', $firstProfileId)
                            ->where('target_profile_id', $secondProfileId);
                    })
                    ->orWhere(function (Builder $query) use ($firstProfileId, $secondProfileId): void {
                        $query
                            ->where('requester_profile_id', $secondProfileId)
                            ->where('target_profile_id', $firstProfileId);
                    });
            })
            ->exists();
    }

    private function freshSession(SportSession $session): SportSession
    {
        return $session
            ->fresh()
            ->load(['creator', 'sport', 'participants', 'participationRecords.profile'])
            ->loadCount(['participants as participant_count' => fn (Builder $query) => $query->whereIn('session_participants.status', SessionParticipantStatus::activeValues())]);
    }

    private function profileForUser(int $userId): ?SportProfile
    {
        return SportProfile::query()
            ->with(['sports', 'availabilityWindows'])
            ->where('user_id', $userId)
            ->first();
    }

    private function requireProfile(int $userId): SportProfile
    {
        return SportProfile::query()
            ->where('user_id', $userId)
            ->firstOrFail();
    }
}
