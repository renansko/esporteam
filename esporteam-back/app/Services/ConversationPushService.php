<?php

namespace App\Services;

use App\Models\PushPreference;
use App\Models\PushSubscription;

class ConversationPushService
{
    public function register(int $userId, array $data): PushSubscription
    {
        return PushSubscription::query()->updateOrCreate(
            ['user_id' => $userId, 'device_id' => $data['device_id']],
            ['endpoint' => $data['endpoint'], 'keys' => $data['keys'], 'active' => true, 'last_seen_at' => now()],
        );
    }

    public function remove(int $userId, ?string $deviceId = null): void
    {
        PushSubscription::query()->where('user_id', $userId)->when($deviceId, fn ($q) => $q->where('device_id', $deviceId))->update(['active' => false]);
    }

    public function preference(int $userId): bool
    {
        return (bool) (PushPreference::query()->where('user_id', $userId)->value('enabled') ?? true);
    }

    public function setPreference(int $userId, bool $enabled): bool
    {
        PushPreference::query()->updateOrCreate(['user_id' => $userId], ['enabled' => $enabled]);
        if (! $enabled) $this->remove($userId);
        return $enabled;
    }
}
