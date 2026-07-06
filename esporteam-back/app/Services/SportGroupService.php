<?php

namespace App\Services;

use App\Models\SportGroup;
use App\Models\SportProfile;
use Illuminate\Support\Facades\DB;

class SportGroupService
{
    public function createForUser(int $userId, array $data): SportGroup
    {
        $profile = $this->requireProfile($userId);

        return DB::transaction(function () use ($profile, $data) {
            $group = SportGroup::query()->create([
                'creator_profile_id' => $profile->id,
                'sport_id' => $data['sport_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'visibility' => $data['visibility'] ?? 'private',
                'capacity' => $data['capacity'] ?? null,
            ]);

            $group->members()->attach($profile->id, [
                'role' => 'owner',
                'status' => 'active',
            ]);

            return $group->load(['creator', 'sport', 'members']);
        });
    }

    public function addMember(int $userId, SportGroup $group, int $profileId, string $role = 'member', string $status = 'active'): SportGroup
    {
        $actor = $this->requireProfile($userId);
        $this->authorizeManager($group, $actor);

        $existing = DB::table('sport_group_members')
            ->where('sport_group_id', $group->id)
            ->where('sport_profile_id', $profileId)
            ->first();

        if ($existing) {
            DB::table('sport_group_members')
                ->where('id', $existing->id)
                ->update([
                    'role' => $existing->role === 'owner' ? 'owner' : $role,
                    'status' => $existing->role === 'owner' ? 'active' : $status,
                    'updated_at' => now(),
                ]);
        } else {
            $group->members()->attach($profileId, [
                'role' => $role,
                'status' => $status,
            ]);
        }

        return $group->load(['creator', 'sport', 'members']);
    }

    public function removeMember(int $userId, SportGroup $group, SportProfile $profile): void
    {
        $actor = $this->requireProfile($userId);
        $this->authorizeManager($group, $actor);

        $updated = DB::table('sport_group_members')
            ->where('sport_group_id', $group->id)
            ->where('sport_profile_id', $profile->id)
            ->where('role', '!=', 'owner')
            ->update([
                'status' => 'left',
                'updated_at' => now(),
            ]);

        if ($updated === 0) {
            abort(404, 'Group member not found.');
        }
    }

    public function authorizeManager(SportGroup $group, SportProfile $actor): void
    {
        $canManage = DB::table('sport_group_members')
            ->where('sport_group_id', $group->id)
            ->where('sport_profile_id', $actor->id)
            ->where('status', 'active')
            ->whereIn('role', ['owner', 'admin'])
            ->exists();

        if (! $canManage) {
            abort(403, 'Only group owners and admins can manage members.');
        }
    }

    private function requireProfile(int $userId): SportProfile
    {
        return SportProfile::query()
            ->where('user_id', $userId)
            ->firstOrFail();
    }
}
