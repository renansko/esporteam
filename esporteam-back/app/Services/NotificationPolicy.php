<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\EventConversationMute;
use App\Models\EventConversationSanction;
use App\Models\EventMessage;
use App\Models\PushPreference;
use App\Models\PushSubscription;

/** Deep module for deciding who may receive a conversation push. */
class NotificationPolicy
{
    public function decide(NotificationActivity $activity): array
    {
        if (! config('features.event_push_notifications', false) || ! in_array($activity->type, ['mention', 'reply', 'announcement'], true)) {
            return [];
        }

        $message = $activity->message->loadMissing(['conversation.session', 'replyTo', 'author']);
        $conversation = $message->conversation;
        $recipientIds = $activity->recipientProfileId !== null
            ? [$activity->recipientProfileId]
            : $this->announcementRecipients($message);

        $deliveries = [];
        foreach (array_unique($recipientIds) as $profileId) {
            if ($profileId === $message->author_profile_id || ! $this->eligible($message, $profileId)) continue;
            $profile = $conversation->session->participants()->whereKey($profileId)->first()
                ?? ($conversation->session->creator_profile_id === $profileId ? $conversation->session->creator : null);
            if ($profile === null) continue;
            foreach (PushSubscription::query()->where('user_id', $profile->user_id)->where('active', true)->get() as $subscription) {
                $deliveries[] = ['profile_id' => $profileId, 'subscription' => $subscription];
            }
        }

        return $deliveries;
    }

    private function eligible(EventMessage $message, int $profileId): bool
    {
        $conversation = $message->conversation;
        $preference = PushPreference::query()->where('user_id', $this->userId($profileId))->value('enabled');
        if ($preference === false) return false;
        if (EventConversationMute::query()->where(['event_conversation_id' => $conversation->id, 'sport_profile_id' => $profileId])->exists()) return false;
        if (EventConversationSanction::query()->where(['event_conversation_id' => $conversation->id, 'sport_profile_id' => $profileId, 'type' => 'ban'])->exists()) return false;
        if ($this->blocked($message->author_profile_id, $profileId)) return false;
        return $conversation->status !== 'archived';
    }

    private function announcementRecipients(EventMessage $message): array
    {
        $session = $message->conversation->session;
        return array_merge([$session->creator_profile_id], $session->participants()->whereIn('session_participants.status', ['joined', 'approved', 'invited', 'interested'])->pluck('sport_profiles.id')->all());
    }

    private function userId(int $profileId): int
    {
        return (int) \App\Models\SportProfile::query()->whereKey($profileId)->value('user_id');
    }

    private function blocked(int $first, int $second): bool
    {
        return Connection::query()->where('type', 'block')->where('status', 'blocked')->where(fn ($q) => $q
            ->where(fn ($q) => $q->where('requester_profile_id', $first)->where('target_profile_id', $second))
            ->orWhere(fn ($q) => $q->where('requester_profile_id', $second)->where('target_profile_id', $first)))->exists();
    }
}
