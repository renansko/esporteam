<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\SportProfile;
use Illuminate\Support\Facades\DB;

class ConnectionService
{
    public function createForUser(int $userId, int $targetProfileId, string $type): Connection
    {
        $requester = $this->requireProfile($userId);

        if ($requester->id === $targetProfileId) {
            abort(422, 'Cannot create a connection with the same profile.');
        }

        [$lowId, $highId] = $this->pair($requester->id, $targetProfileId);

        if ($type === 'block') {
            return DB::transaction(function () use ($requester, $targetProfileId, $lowId, $highId) {
                Connection::query()
                    ->where('profile_low_id', $lowId)
                    ->where('profile_high_id', $highId)
                    ->whereIn('type', ['friendship', 'interest'])
                    ->whereIn('status', ['pending', 'accepted', 'interested'])
                    ->delete();

                $connection = Connection::query()->updateOrCreate(
                    [
                        'profile_low_id' => $lowId,
                        'profile_high_id' => $highId,
                        'type' => 'block',
                    ],
                    [
                        'requester_profile_id' => $requester->id,
                        'target_profile_id' => $targetProfileId,
                        'status' => 'blocked',
                    ],
                );

                return $connection->load(['requester', 'target']);
            });
        }

        $existing = Connection::query()
            ->where('profile_low_id', $lowId)
            ->where('profile_high_id', $highId)
            ->whereIn('type', ['friendship', 'interest', 'block'])
            ->first();

        if ($existing) {
            abort(422, 'Connection already exists for these profiles.');
        }

        $connection = Connection::query()->create([
            'requester_profile_id' => $requester->id,
            'target_profile_id' => $targetProfileId,
            'profile_low_id' => $lowId,
            'profile_high_id' => $highId,
            'type' => $type,
            'status' => $type === 'interest' ? 'interested' : 'pending',
        ]);

        return $connection->load(['requester', 'target']);
    }

    public function updateForUser(int $userId, Connection $connection, string $status): Connection
    {
        $profile = $this->requireProfile($userId);

        if ($connection->type !== 'friendship' || $connection->status !== 'pending') {
            abort(422, 'Only pending friendship requests can be updated.');
        }

        if ($connection->target_profile_id !== $profile->id) {
            abort(403, 'Only the target profile can answer this connection.');
        }

        $connection->update(['status' => $status]);

        return $connection->load(['requester', 'target']);
    }

    private function requireProfile(int $userId): SportProfile
    {
        return SportProfile::query()
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    private function pair(int $firstProfileId, int $secondProfileId): array
    {
        return [
            min($firstProfileId, $secondProfileId),
            max($firstProfileId, $secondProfileId),
        ];
    }
}
