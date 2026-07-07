<?php

namespace App\Services;

use App\Enums\SessionParticipantStatus;
use App\Enums\SportSessionStatus;
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
    /**
     * @wiki app/brain/functions/SportSessionService.md#createForUser
     */
    public function createForUser(int $userId, array $data): SportSession
    {
        $profile = $this->requireProfile($userId);

        return DB::transaction(function () use ($profile, $data) {
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
    public function openSessions(array $filters = []): Collection
    {
        return SportSession::query()
            ->with(['creator', 'sport'])
            ->withCount(['participants as participant_count' => fn (Builder $query) => $query->where('session_participants.status', SessionParticipantStatus::Joined->value)])
            ->where('status', SportSessionStatus::Open->value)
            ->where('visibility', 'public')
            ->when(isset($filters['sport_id']), fn (Builder $query) => $query->where('sport_id', (int) $filters['sport_id']))
            ->when(isset($filters['sport_slug']), fn (Builder $query) => $query->whereHas('sport', fn (Builder $query) => $query->where('slug', $filters['sport_slug'])))
            ->when(isset($filters['type']), fn (Builder $query) => $query->where('type', $filters['type']))
            ->when(isset($filters['city']), fn (Builder $query) => $query->where('city', $filters['city']))
            ->when(isset($filters['region']), fn (Builder $query) => $query->where('region', $filters['region']))
            ->when(isset($filters['starts_after']), fn (Builder $query) => $query->where('starts_at', '>=', $filters['starts_after']))
            ->when(isset($filters['starts_before']), fn (Builder $query) => $query->where('starts_at', '<=', $filters['starts_before']))
            ->orderBy('starts_at')
            ->orderBy('id')
            ->limit(50)
            ->get();
    }

    /**
     * @wiki app/brain/functions/SportSessionService.md#join
     */
    public function join(int $userId, SportSession $session): SportSession
    {
        $profile = $this->requireProfile($userId);

        return DB::transaction(function () use ($profile, $session) {
            $lockedSession = SportSession::query()
                ->whereKey($session->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSession->status !== SportSessionStatus::Open) {
                throw ValidationException::withMessages([
                    'session' => 'Session is not open for joining.',
                ]);
            }

            $activeParticipantCount = DB::table('session_participants')
                ->where('sport_session_id', $lockedSession->id)
                ->where('status', SessionParticipantStatus::Joined->value)
                ->count();

            if ($lockedSession->capacity !== null && $activeParticipantCount >= $lockedSession->capacity) {
                throw ValidationException::withMessages([
                    'capacity' => 'Session capacity is full.',
                ]);
            }

            $existing = DB::table('session_participants')
                ->where('sport_session_id', $lockedSession->id)
                ->where('sport_profile_id', $profile->id)
                ->first();

            if ($existing?->status === SessionParticipantStatus::Joined->value) {
                throw ValidationException::withMessages([
                    'profile' => 'Sport profile already joined this session.',
                ]);
            }

            if ($existing) {
                DB::table('session_participants')
                    ->where('id', $existing->id)
                    ->update([
                        'status' => SessionParticipantStatus::Joined->value,
                        'updated_at' => now(),
                    ]);
            } else {
                $lockedSession->participants()->attach($profile->id, [
                    'status' => SessionParticipantStatus::Joined->value,
                ]);
            }

            return $this->freshSession($lockedSession);
        });
    }

    private function freshSession(SportSession $session): SportSession
    {
        return $session
            ->fresh()
            ->load(['creator', 'sport', 'participants'])
            ->loadCount(['participants as participant_count' => fn (Builder $query) => $query->where('session_participants.status', SessionParticipantStatus::Joined->value)]);
    }

    private function requireProfile(int $userId): SportProfile
    {
        return SportProfile::query()
            ->where('user_id', $userId)
            ->firstOrFail();
    }
}
