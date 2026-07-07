<?php

namespace App\Services;

use App\Enums\SessionParticipantStatus;
use App\Enums\SportSessionEntryMode;
use App\Enums\SportSessionStatus;
use App\Enums\SportSessionType;
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
 * @wiki app/brain/services/PostMatchSportActionService.md
 */
class PostMatchSportActionService
{
    private const WEEKDAY_REASONS = [
        0 => 'available_sunday',
        1 => 'available_monday',
        2 => 'available_tuesday',
        3 => 'available_wednesday',
        4 => 'available_thursday',
        5 => 'available_friday',
        6 => 'available_saturday',
    ];

    public function __construct(
        private readonly SportSessionService $sessions,
    ) {}

    /**
     * @wiki app/brain/functions/PostMatchSportActionService.md#actionsForUser
     */
    public function actionsForUser(int $userId, array $data): array
    {
        $context = $this->contextForUser($userId, $data);
        $profiles = $context['profiles'];
        $timeSuggestions = $this->timeSuggestions($profiles);
        $locationSuggestions = $this->locationSuggestions($profiles, $this->commonSportId($profiles));
        $reasons = $this->contextReasons($context, $profiles);

        return [
            'context' => [
                'type' => $context['type'],
                'connection_id' => $context['connection']?->id,
                'session_id' => $context['session']?->id,
                'profile_ids' => $profiles->pluck('id')->values()->all(),
            ],
            'next_actions' => $this->nextActions($context, $timeSuggestions, $locationSuggestions, $reasons),
            'time_suggestions' => $timeSuggestions,
            'location_suggestions' => $locationSuggestions,
            'reasons' => $reasons,
        ];
    }

    /**
     * @wiki app/brain/functions/PostMatchSportActionService.md#saveSessionForUser
     */
    public function saveSessionForUser(int $userId, array $data): SportSession
    {
        $context = $this->contextForUser($userId, $data);

        if ($context['type'] === 'connection') {
            return $this->createSessionFromConnection($userId, $context, $data);
        }

        return $this->linkExistingSession($context, $data);
    }

    private function createSessionFromConnection(int $userId, array $context, array $data): SportSession
    {
        /** @var Collection<int, SportProfile> $profiles */
        $profiles = $context['profiles'];
        $capacity = (int) ($data['capacity'] ?? $profiles->count());

        if ($capacity < $profiles->count()) {
            throw ValidationException::withMessages([
                'capacity' => 'Session capacity must fit every accepted match profile.',
            ]);
        }

        return DB::transaction(function () use ($userId, $profiles, $data, $capacity): SportSession {
            $session = $this->sessions->createForUser($userId, [
                'sport_id' => $data['sport_id'] ?? $this->commonSportId($profiles),
                'title' => $data['title'] ?? $this->defaultSessionTitle($profiles),
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? SportSessionType::Match->value,
                'starts_at' => $data['starts_at'],
                'location_label' => $data['location_label'] ?? null,
                'city' => $data['city'] ?? null,
                'region' => $data['region'] ?? null,
                'latitude_approx' => $data['latitude_approx'] ?? null,
                'longitude_approx' => $data['longitude_approx'] ?? null,
                'capacity' => $capacity,
                'entry_mode' => SportSessionEntryMode::InviteOnly->value,
                'visibility' => 'private',
                'status' => SportSessionStatus::Open->value,
            ]);

            $currentProfile = $this->requireProfile($userId);

            foreach ($profiles as $profile) {
                if ($profile->id === $currentProfile->id) {
                    continue;
                }

                $this->upsertParticipation($session->id, $profile->id, SessionParticipantStatus::Approved);
            }

            return $this->freshSession($session);
        });
    }

    private function linkExistingSession(array $context, array $data): SportSession
    {
        /** @var SportSession $session */
        $session = $context['session'];

        return DB::transaction(function () use ($session, $data): SportSession {
            $lockedSession = SportSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedSession->fill(array_filter([
                'starts_at' => $data['starts_at'] ?? null,
                'location_label' => $data['location_label'] ?? null,
                'city' => $data['city'] ?? null,
                'region' => $data['region'] ?? null,
                'latitude_approx' => $data['latitude_approx'] ?? null,
                'longitude_approx' => $data['longitude_approx'] ?? null,
            ], fn ($value) => $value !== null));
            $lockedSession->save();

            return $this->freshSession($lockedSession);
        });
    }

    private function contextForUser(int $userId, array $data): array
    {
        $profile = $this->requireProfile($userId);

        if (isset($data['connection_id'])) {
            return $this->connectionContext($profile, (int) $data['connection_id']);
        }

        return $this->sessionContext($profile, (int) $data['session_id']);
    }

    private function connectionContext(SportProfile $profile, int $connectionId): array
    {
        $connection = Connection::query()
            ->with(['requester.sports.sport', 'requester.availabilityWindows', 'target.sports.sport', 'target.availabilityWindows'])
            ->findOrFail($connectionId);

        if (! in_array($profile->id, [$connection->requester_profile_id, $connection->target_profile_id], true)) {
            abort(403, 'Only matched sport profiles can see post-match actions.');
        }

        if ($connection->type !== 'friendship' || $connection->status !== 'accepted') {
            throw ValidationException::withMessages([
                'connection_id' => 'Post-match actions require an accepted one-to-one match.',
            ]);
        }

        return [
            'type' => 'connection',
            'connection' => $connection,
            'session' => null,
            'profiles' => collect([$connection->requester, $connection->target])->values(),
        ];
    }

    private function sessionContext(SportProfile $profile, int $sessionId): array
    {
        $session = SportSession::query()
            ->with([
                'sport',
                'participationRecords.profile.sports.sport',
                'participationRecords.profile.availabilityWindows',
            ])
            ->findOrFail($sessionId);

        if ($session->status !== SportSessionStatus::Open) {
            throw ValidationException::withMessages([
                'session_id' => 'Post-match actions require an open group session.',
            ]);
        }

        $activeParticipationRecords = $session->participationRecords
            ->filter(fn (SessionParticipant $participation) => in_array($participation->status->value, SessionParticipantStatus::activeValues(), true))
            ->values();
        $activeParticipants = $activeParticipationRecords
            ->pluck('profile')
            ->values();

        if (! $activeParticipants->contains('id', $profile->id)) {
            abort(403, 'Only active session participants can see post-match actions.');
        }

        if (
            $activeParticipants->count() < 2
            || ! $activeParticipationRecords->contains(fn (SessionParticipant $participation) => $participation->sport_profile_id !== $session->creator_profile_id
                && $participation->status === SessionParticipantStatus::Approved)
        ) {
            throw ValidationException::withMessages([
                'session_id' => 'Post-match actions require an accepted group match.',
            ]);
        }

        return [
            'type' => 'session',
            'connection' => null,
            'session' => $session,
            'profiles' => $activeParticipants,
        ];
    }

    private function nextActions(array $context, array $timeSuggestions, array $locationSuggestions, array $reasons): array
    {
        $hasTime = $timeSuggestions !== [];
        $hasLocation = $locationSuggestions !== [];
        $sessionAction = $context['type'] === 'connection' ? 'criar_sessao' : 'vincular_sessao';

        return [
            [
                'type' => 'propor_horario',
                'available' => $hasTime,
                'reason' => $hasTime ? $timeSuggestions[0]['reason'] : 'no_shared_availability',
            ],
            [
                'type' => 'escolher_local',
                'available' => $hasLocation,
                'reason' => $hasLocation ? $locationSuggestions[0]['reason'] : 'no_location_context',
            ],
            [
                'type' => $sessionAction,
                'available' => true,
                'reason' => $context['type'] === 'connection' ? ($reasons[0] ?? 'accepted_match') : 'active_group',
            ],
            [
                'type' => 'confirmar_presenca',
                'available' => $context['type'] === 'session',
                'reason' => $context['type'] === 'session' ? 'active_group' : 'create_session_first',
            ],
        ];
    }

    private function timeSuggestions(Collection $profiles): array
    {
        $windowsByProfile = $profiles
            ->map(fn (SportProfile $profile) => $profile->availabilityWindows->map(fn ($window) => [
                'weekday' => (int) $window->weekday,
                'starts_at' => substr((string) $window->starts_at, 0, 5),
                'ends_at' => substr((string) $window->ends_at, 0, 5),
            ]))
            ->values();

        if ($windowsByProfile->contains(fn (Collection $windows) => $windows->isEmpty())) {
            return [];
        }

        $common = $windowsByProfile->shift()->values();

        foreach ($windowsByProfile as $profileWindows) {
            $common = $common
                ->flatMap(fn (array $commonWindow) => $profileWindows
                    ->map(fn (array $window) => $this->overlapWindow($commonWindow, $window))
                    ->filter())
                ->values();
        }

        return $common
            ->map(fn (array $window) => [
                'weekday' => $window['weekday'],
                'starts_at' => $window['starts_at'],
                'ends_at' => $window['ends_at'],
                'reason' => self::WEEKDAY_REASONS[$window['weekday']],
            ])
            ->unique(fn (array $window) => "{$window['weekday']}-{$window['starts_at']}-{$window['ends_at']}")
            ->sortBy([
                ['weekday', 'asc'],
                ['starts_at', 'asc'],
            ])
            ->take(5)
            ->values()
            ->all();
    }

    private function overlapWindow(array $current, array $candidate): ?array
    {
        if ($current['weekday'] !== $candidate['weekday']) {
            return null;
        }

        $startsAt = max($current['starts_at'], $candidate['starts_at']);
        $endsAt = min($current['ends_at'], $candidate['ends_at']);

        if ($startsAt >= $endsAt) {
            return null;
        }

        return [
            'weekday' => $current['weekday'],
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ];
    }

    private function locationSuggestions(Collection $profiles, ?int $sportId): array
    {
        $suggestions = [];
        $profilesWithArea = $profiles->filter(fn (SportProfile $profile) => $profile->city !== null || $profile->region !== null);

        if ($profilesWithArea->isNotEmpty()) {
            $suggestions[] = [
                'type' => 'profile_area',
                'city' => $profilesWithArea->pluck('city')->filter()->countBy()->sortDesc()->keys()->first(),
                'region' => $profilesWithArea->pluck('region')->filter()->countBy()->sortDesc()->keys()->first(),
                'reason' => 'nearby',
            ];
        }

        $profilesWithCoordinates = $profiles->filter(fn (SportProfile $profile) => $profile->latitude_approx !== null && $profile->longitude_approx !== null);
        if ($profilesWithCoordinates->isNotEmpty()) {
            $suggestions[] = [
                'type' => 'approx_midpoint',
                'latitude_approx' => round($profilesWithCoordinates->avg('latitude_approx'), 5),
                'longitude_approx' => round($profilesWithCoordinates->avg('longitude_approx'), 5),
                'reason' => 'nearby',
            ];
        }

        $profilesWithSearchableArea = $profiles->filter(fn (SportProfile $profile) => $profile->city !== null || $profile->region !== null);
        $nearbySessions = SportSession::query()
            ->where('status', SportSessionStatus::Open->value)
            ->where('visibility', 'public')
            ->when($sportId !== null, fn (Builder $query) => $query->where('sport_id', $sportId))
            ->when($profilesWithSearchableArea->isNotEmpty(), function (Builder $query) use ($profilesWithSearchableArea): void {
                $query->where(function (Builder $query) use ($profilesWithSearchableArea): void {
                    foreach ($profilesWithSearchableArea as $profile) {
                        $query->orWhere(function (Builder $query) use ($profile): void {
                            $query
                                ->when($profile->city !== null, fn (Builder $query) => $query->where('city', $profile->city))
                                ->when($profile->region !== null, fn (Builder $query) => $query->where('region', $profile->region));
                        });
                    }
                });
            })
            ->when($profilesWithSearchableArea->isEmpty(), function (Builder $query): void {
                $query->whereRaw('1 = 0');
            })
            ->orderBy('starts_at')
            ->orderBy('id')
            ->limit(3)
            ->get();

        foreach ($nearbySessions as $session) {
            $suggestions[] = [
                'type' => 'nearby_session',
                'session_id' => $session->id,
                'title' => $session->title,
                'starts_at' => $session->starts_at?->toISOString(),
                'location_label' => $session->location_label,
                'city' => $session->city,
                'region' => $session->region,
                'reason' => 'nearby_session',
            ];
        }

        return array_slice($suggestions, 0, 5);
    }

    private function contextReasons(array $context, Collection $profiles): array
    {
        $reasons = [];

        if ($this->commonSportId($profiles) !== null) {
            $reasons[] = 'same_sport';
        }

        if ($this->hasCompatibleLevel($profiles)) {
            $reasons[] = 'compatible_level';
        }

        if ($this->timeSuggestions($profiles) !== []) {
            $reasons[] = 'available';
        }

        if ($context['type'] === 'session') {
            $reasons[] = 'active_group';
        }

        return array_values(array_unique($reasons));
    }

    private function commonSportId(Collection $profiles): ?int
    {
        $sportIdSets = $profiles
            ->map(fn (SportProfile $profile) => $profile->sports->pluck('sport_id')->all())
            ->values()
            ->all();

        if ($sportIdSets === [] || collect($sportIdSets)->contains(fn (array $ids) => $ids === [])) {
            return null;
        }

        $common = array_shift($sportIdSets);
        foreach ($sportIdSets as $sportIds) {
            $common = array_values(array_intersect($common, $sportIds));
        }

        return $common[0] ?? null;
    }

    private function hasCompatibleLevel(Collection $profiles): bool
    {
        $levels = $profiles
            ->flatMap(fn (SportProfile $profile) => $profile->sports->map(fn (ProfileSport $sport) => $sport->level?->value))
            ->filter()
            ->countBy();

        return $levels->contains(fn (int $count) => $count > 1);
    }

    private function defaultSessionTitle(Collection $profiles): string
    {
        return 'Pratica esportiva com '.implode(', ', $profiles->pluck('display_name')->all());
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

    private function freshSession(SportSession $session): SportSession
    {
        return $session
            ->fresh()
            ->load(['creator', 'sport', 'participants', 'participationRecords.profile'])
            ->loadCount(['participants as participant_count' => fn (Builder $query) => $query->whereIn('session_participants.status', SessionParticipantStatus::activeValues())]);
    }

    private function requireProfile(int $userId): SportProfile
    {
        return SportProfile::query()
            ->where('user_id', $userId)
            ->firstOrFail();
    }
}
