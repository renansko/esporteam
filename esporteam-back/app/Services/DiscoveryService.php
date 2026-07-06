<?php

namespace App\Services;

use App\Enums\ProfileVisibility;
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
        $currentProfileId = SportProfile::query()
            ->where('user_id', $userId)
            ->value('id');

        return SportProfile::query()
            ->with(['sports.sport', 'availabilityWindows'])
            ->where('visibility', ProfileVisibility::Public->value)
            ->when($currentProfileId, fn (Builder $query) => $query->whereKeyNot($currentProfileId))
            ->when($this->hasAvailabilityFilter($filters), fn (Builder $query) => $this->filterByAvailabilityOverlap($query, $filters))
            ->orderBy('display_name')
            ->orderBy('id')
            ->limit(50)
            ->get();
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
}
