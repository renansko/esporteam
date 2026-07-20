<?php

namespace App\Services;

use App\Events\EventConversationMessagePosted;
use App\Events\EventConversationSocialStateChanged;
use App\Jobs\DeliverConversationPush;
use App\Models\Connection;
use App\Models\EventConversation;
use App\Models\EventConversationMute;
use App\Models\EventConversationAudit;
use App\Models\EventConversationSanction;
use App\Models\EventConversationRead;
use App\Models\EventMessage;
use App\Models\EventMessageMention;
use App\Models\EventMessageReaction;
use App\Models\SportProfile;
use App\Models\SportSession;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Durable, authorized conversation for one-off Sport Sessions.
 *
 * @wiki app/brain/services/EventConversationService.md
 */
class EventConversationService
{
    private const PARTICIPANT_STATUSES = ['joined', 'approved', 'invited', 'interested'];

    /** @wiki app/brain/functions/EventConversationService.md#openConversation */
    public function openConversation(int $userId, SportSession $session, ?int $cursor, int $limit): array
    {
        $profile = $this->requireProfile($userId);
        $this->assertMayAccess($profile, $session);
        $this->rateLimit('read', $profile->id, $session->id, 60, 60);
        $conversation = $this->conversationFor($session);
        $messages = EventMessage::query()
            ->with(['author', 'replyTo.author', 'mentions.profile', 'reactions', 'media.media'])
            ->where('event_conversation_id', $conversation->id)
            ->when($cursor !== null, fn ($query) => $query->where('id', '>', $cursor))
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();
        $hasMore = $messages->count() > $limit;
        $messages = $messages->take($limit)->values();
        foreach ($messages as $message) {
            if ($message->author_profile_id === $profile->id) {
                $message->setAttribute('seen_by_count', EventConversationRead::query()
                    ->where('event_conversation_id', $conversation->id)
                    ->where('sport_profile_id', '!=', $profile->id)
                    ->where('last_read_message_id', '>=', $message->id)->count());
            }
        }

        return [
            'conversation' => ['id' => $conversation->id, 'session_id' => $session->id, 'status' => $conversation->status, 'muted' => EventConversationMute::query()->where('event_conversation_id', $conversation->id)->where('sport_profile_id', $profile->id)->exists()],
            'messages' => $messages,
            'next_cursor' => $hasMore ? (string) $messages->last()->id : null,
        ];
    }

    /** @wiki app/brain/functions/EventConversationService.md#postMessage */
    public function postMessage(int $userId, SportSession $session, ?string $body, string $clientMessageId, ?int $replyToMessageId = null, array $mediaIds = []): EventMessage
    {
        $profile = $this->requireProfile($userId);
        $this->assertMayAccess($profile, $session);
        $this->rateLimit('write', $profile->id, $session->id, 20, 60);
        $body = trim(strip_tags((string) $body));
        if ($body === '' && $mediaIds === []) {
            throw ValidationException::withMessages(['body' => 'A mensagem precisa conter texto.']);
        }

        return DB::transaction(function () use ($profile, $session, $body, $clientMessageId, $replyToMessageId, $mediaIds): EventMessage {
            $conversation = $this->conversationFor($session);
            $this->assertWritable($profile, $conversation);
            $existing = EventMessage::query()
                ->with('author')
                ->where('event_conversation_id', $conversation->id)
                ->where('author_profile_id', $profile->id)
                ->where('client_message_id', $clientMessageId)
                ->first();
            if ($existing !== null) {
                return $existing->load(['media.media']);
            }

            try {
                $message = EventMessage::query()->create([
                    'event_conversation_id' => $conversation->id,
                    'reply_to_event_message_id' => $replyToMessageId,
                    'author_profile_id' => $profile->id,
                    'client_message_id' => $clientMessageId,
                    'body' => $body,
                ]);
                if ($mediaIds !== []) {
                    $media = app(ConversationMediaService::class)->approvedForMessage($conversation, $profile->id, $mediaIds);
                    foreach ($media as $position => $item) $message->media()->create(['conversation_media_id' => $item->id, 'position' => $position]);
                }
                $message->load(['author', 'replyTo.author', 'mentions.profile', 'reactions', 'media.media']);
            } catch (QueryException $exception) {
                $message = EventMessage::query()->with('author')
                    ->where('event_conversation_id', $conversation->id)
                    ->where('author_profile_id', $profile->id)
                    ->where('client_message_id', $clientMessageId)->first()?->load(['media.media']);
                if ($message === null) {
                    throw $exception;
                }

                return $message;
            }

            DB::afterCommit(function () use ($message): void {
                try {
                    broadcast(new EventConversationMessagePosted($message));
                } catch (\Throwable $exception) {
                    Log::warning('event_conversation.broadcast_failed', ['message_id' => $message->id, 'exception' => $exception::class]);
                }
                if ($message->reply_to_event_message_id !== null) {
                    DeliverConversationPush::dispatch($message->id, 'reply', $message->replyTo?->author_profile_id);
                }
            });

            return $message;
        });
    }

    /** Applies all durable and ephemeral social intentions through one module boundary. */
    public function applySocialAction(int $userId, SportSession $session, array $command): array
    {
        $profile = $this->requireProfile($userId);
        $this->assertMayAccess($profile, $session);
        $conversation = $this->conversationFor($session);
        $action = $command['action'];
        if (! in_array($action, ['read', 'mute'], true)) {
            $this->assertWritable($profile, $conversation);
        }

        return match ($action) {
            'reply' => ['message' => $this->reply($profile, $session, $conversation, $command)],
            'mention' => $this->mention($profile, $session, $conversation, $command),
            'reaction' => $this->react($profile, $conversation, $command),
            'read' => $this->read($profile, $conversation, $command),
            'mute' => $this->mute($profile, $conversation, $command),
            'typing' => $this->typing($profile, $conversation, $command),
            'remove' => ['message' => $this->remove($profile, $conversation, $command)],
            'hide' => ['message' => $this->hide($profile, $session, $conversation, $command)],
            'mute_profile', 'ban' => $this->sanction($profile, $session, $conversation, $action, $command),
            'announce' => ['message' => $this->announce($profile, $session, $conversation, $command)],
        };
    }

    private function reply(SportProfile $profile, SportSession $session, EventConversation $conversation, array $command): EventMessage
    {
        foreach (['message_id', 'body', 'client_message_id'] as $field) {
            if (empty($command[$field])) {
                throw ValidationException::withMessages([$field => 'Obrigatório para responder.']);
            }
        }
        $replyTo = $this->messageInConversation($conversation, (int) $command['message_id']);

        return $this->postMessage($profile->user_id, $session, $command['body'], $command['client_message_id'], $replyTo->id)
            ->fresh(['author', 'replyTo.author', 'mentions.profile', 'reactions']);
    }

    private function mention(SportProfile $profile, SportSession $session, EventConversation $conversation, array $command): array
    {
        if (empty($command['message_id']) || empty($command['mentioned_profile_id'])) {
            throw ValidationException::withMessages(['message_id' => 'Mensagem e perfil são obrigatórios para mencionar.']);
        }
        $message = $this->messageInConversation($conversation, (int) $command['message_id']);
        $eligible = $this->mentionableProfileIds($session, $conversation);
        if (! in_array((int) $command['mentioned_profile_id'], $eligible, true)) {
            abort(403, 'This profile cannot be mentioned in this conversation.');
        }
        EventMessageMention::query()->firstOrCreate(['event_message_id' => $message->id, 'mentioned_profile_id' => $command['mentioned_profile_id']]);
        DeliverConversationPush::dispatch($message->id, 'mention', (int) $command['mentioned_profile_id']);
        $message = $message->fresh(['author', 'replyTo.author', 'mentions.profile', 'reactions']);
        $this->broadcastSocial($conversation->id, 'message', $message);

        return ['message' => $message, 'mentioned_profile_id' => (int) $command['mentioned_profile_id']];
    }

    private function react(SportProfile $profile, EventConversation $conversation, array $command): array
    {
        if (empty($command['message_id']) || empty($command['emoji'])) {
            throw ValidationException::withMessages(['message_id' => 'Mensagem e emoji são obrigatórios para reagir.']);
        }
        $message = $this->messageInConversation($conversation, (int) $command['message_id']);
        if (($command['active'] ?? true) === false) {
            EventMessageReaction::query()->where(['event_message_id' => $message->id, 'sport_profile_id' => $profile->id, 'emoji' => $command['emoji']])->delete();
        } else {
            EventMessageReaction::query()->firstOrCreate(['event_message_id' => $message->id, 'sport_profile_id' => $profile->id, 'emoji' => $command['emoji']]);
        }
        $message = $message->fresh(['author', 'replyTo.author', 'mentions.profile', 'reactions']);
        $this->broadcastSocial($conversation->id, 'message', $message);

        return ['message' => $message];
    }

    private function read(SportProfile $profile, EventConversation $conversation, array $command): array
    {
        $cursor = (int) ($command['cursor'] ?? 0);
        if ($cursor > 0 && ! EventMessage::query()->where('event_conversation_id', $conversation->id)->whereKey($cursor)->exists()) {
            throw ValidationException::withMessages(['cursor' => 'A leitura precisa apontar para uma mensagem desta conversa.']);
        }

        return DB::transaction(function () use ($profile, $conversation, $cursor): array {
            EventConversation::query()->lockForUpdate()->findOrFail($conversation->id);
            $read = EventConversationRead::query()->lockForUpdate()->firstOrCreate([
                'event_conversation_id' => $conversation->id,
                'sport_profile_id' => $profile->id,
            ]);
            if ($cursor > $read->last_read_message_id) {
                $read->update(['last_read_message_id' => $cursor]);
            }

            return ['cursor' => (int) $read->fresh()->last_read_message_id];
        });
    }

    private function mute(SportProfile $profile, EventConversation $conversation, array $command): array
    {
        if (($command['muted'] ?? true) === false) {
            EventConversationMute::query()->where(['event_conversation_id' => $conversation->id, 'sport_profile_id' => $profile->id])->delete();
        } else {
            EventConversationMute::query()->firstOrCreate(['event_conversation_id' => $conversation->id, 'sport_profile_id' => $profile->id]);
        }

        return ['muted' => ($command['muted'] ?? true) !== false];
    }

    private function typing(SportProfile $profile, EventConversation $conversation, array $command): array
    {
        $this->broadcastSocial($conversation->id, 'typing', null, ['profile_id' => $profile->id, 'active' => ($command['active'] ?? true) !== false]);

        return ['active' => ($command['active'] ?? true) !== false];
    }

    private function remove(SportProfile $profile, EventConversation $conversation, array $command): EventMessage
    {
        $message = $this->messageInConversation($conversation, (int) ($command['message_id'] ?? 0));
        if ($message->author_profile_id !== $profile->id) {
            abort(403, 'Only the author can remove this message.');
        }

        return $this->moderateMessage($profile, $conversation, $message, 'removed', null);
    }

    private function hide(SportProfile $profile, SportSession $session, EventConversation $conversation, array $command): EventMessage
    {
        if ($session->creator_profile_id !== $profile->id) {
            abort(403, 'Only the session host can hide a message.');
        }
        $message = $this->messageInConversation($conversation, (int) ($command['message_id'] ?? 0));

        return $this->moderateMessage($profile, $conversation, $message, 'hidden', $command['reason'] ?? null);
    }

    private function sanction(SportProfile $actor, SportSession $session, EventConversation $conversation, string $type, array $command): array
    {
        $this->assertHost($actor, $session);
        $targetId = (int) ($command['target_profile_id'] ?? 0);
        if ($targetId === 0 || $targetId === $actor->id) {
            throw ValidationException::withMessages(['target_profile_id' => 'Escolha outro Perfil Esportivo.']);
        }
        $target = SportProfile::query()->findOrFail($targetId);
        $before = EventConversationSanction::query()->where([
            'event_conversation_id' => $conversation->id, 'sport_profile_id' => $target->id, 'type' => $type,
        ])->first();
        $sanction = EventConversationSanction::query()->updateOrCreate([
            'event_conversation_id' => $conversation->id, 'sport_profile_id' => $target->id, 'type' => $type,
        ], [
            'imposed_by_profile_id' => $actor->id, 'reason' => $command['reason'] ?? null,
            'expires_at' => $type === 'mute_profile' ? ($command['expires_at'] ?? null) : null,
        ]);
        $this->audit($conversation, $actor->id, $target->id, $type, $command['reason'] ?? null, $before?->only(['type', 'reason', 'expires_at']), $sanction->only(['type', 'reason', 'expires_at']));
        $this->broadcastSocial($conversation->id, 'sanction', null, ['profile_id' => $target->id, 'type' => $type]);

        return ['sanction' => ['profile_id' => $target->id, 'type' => $type, 'expires_at' => $sanction->expires_at?->toISOString()]];
    }

    private function announce(SportProfile $actor, SportSession $session, EventConversation $conversation, array $command): EventMessage
    {
        $this->assertHost($actor, $session);
        $body = trim(strip_tags((string) ($command['body'] ?? '')));
        if ($body === '') throw ValidationException::withMessages(['body' => 'O anúncio precisa conter texto.']);
        $key = $command['client_message_id'] ?? null;
        if ($key === null) throw ValidationException::withMessages(['client_message_id' => 'Obrigatório para anúncio.']);
        $message = EventMessage::query()->firstOrCreate([
            'event_conversation_id' => $conversation->id, 'author_profile_id' => $actor->id, 'client_message_id' => $key,
        ], ['body' => $body, 'kind' => 'announcement']);
        $message->load(['author', 'replyTo.author', 'mentions.profile', 'reactions', 'media.media']);
        $this->audit($conversation, $actor->id, null, 'announcement', null, null, ['message_id' => $message->id]);
        $this->broadcastSocial($conversation->id, 'message', $message);
        DB::afterCommit(fn () => DeliverConversationPush::dispatch($message->id, 'announcement'));
        return $message;
    }

    private function moderateMessage(SportProfile $actor, EventConversation $conversation, EventMessage $message, string $status, ?string $reason): EventMessage
    {
        if ($message->status === 'removed' || $message->status === 'hidden') {
            return $message->load(['author', 'replyTo.author', 'mentions.profile', 'reactions', 'media.media']);
        }
        $message->update([
            'status' => $status,
            'moderated_by_profile_id' => $actor->id,
            'moderation_reason' => $reason,
            'moderated_at' => now(),
        ]);
        $this->audit($conversation, $actor->id, $message->author_profile_id, $status, $reason, ['status' => 'published'], ['status' => $status]);
        $message = $message->fresh(['author', 'replyTo.author', 'mentions.profile', 'reactions', 'media.media']);
        $this->broadcastSocial($conversation->id, 'message', $message);

        return $message;
    }

    private function messageInConversation(EventConversation $conversation, int $messageId): EventMessage
    {
        return EventMessage::query()->where('event_conversation_id', $conversation->id)->findOr($messageId, fn () => throw new NotFoundHttpException);
    }

    /** Host plus profiles who participated or have already written in this conversation. */
    private function mentionableProfileIds(SportSession $session, EventConversation $conversation): array
    {
        return array_values(array_unique([
            $session->creator_profile_id,
            ...$session->participationRecords()->whereIn('status', self::PARTICIPANT_STATUSES)->pluck('sport_profile_id')->all(),
            ...EventMessage::query()->where('event_conversation_id', $conversation->id)->pluck('author_profile_id')->all(),
        ]));
    }

    private function broadcastSocial(int $conversationId, string $kind, ?EventMessage $message = null, array $state = []): void
    {
        DB::afterCommit(function () use ($conversationId, $kind, $message, $state): void {
            try {
                broadcast(new EventConversationSocialStateChanged($conversationId, $kind, $message, $state));
            } catch (\Throwable $exception) {
                Log::warning('event_conversation.social_broadcast_failed', ['conversation_id' => $conversationId, 'exception' => $exception::class]);
            }
        });
    }

    public function mayAccessUser(int $userId, SportSession $session): bool
    {
        $profile = SportProfile::query()->where('user_id', $userId)->first();
        if ($profile === null) {
            return false;
        }
        try {
            $this->assertMayAccess($profile, $session);

            return true;
        } catch (NotFoundHttpException) {
            return false;
        }
    }

    /** Authorizes access and returns the canonical conversation for adjacent deep modules. */
    public function authorizedConversation(int $userId, SportSession $session): EventConversation
    {
        $profile = $this->requireProfile($userId);
        $this->assertMayAccess($profile, $session);
        return $this->conversationFor($session);
    }

    /** Archive a point-in-time conversation and leave an auditable system notice. */
    public function archiveCancelledSession(SportSession $session): void
    {
        if ($session->sport_session_series_id !== null) return;
        $conversation = $this->conversationFor($session);
        if ($conversation->status === 'archived') return;
        $conversation->update(['status' => 'archived', 'archived_at' => now()]);
        EventMessage::query()->create([
            'event_conversation_id' => $conversation->id, 'author_profile_id' => $session->creator_profile_id,
            'client_message_id' => (string) \Illuminate\Support\Str::uuid(), 'body' => 'Esta Sessão Esportiva foi cancelada.', 'kind' => 'system',
        ]);
        $this->audit($conversation, null, null, 'archived_cancelled', $session->cancelled_reason, ['status' => 'active'], ['status' => 'archived']);
        $this->broadcastSocial($conversation->id, 'lifecycle', null, ['status' => 'archived']);
    }

    public function archiveExpiredConversations(): int
    {
        return EventConversation::query()->where('status', 'active')->whereHas('session', fn ($q) => $q->whereNotNull('ends_at')->where('ends_at', '<=', now()->subDay())->whereNull('sport_session_series_id'))
            ->update(['status' => 'archived', 'archived_at' => now()]);
    }

    public function profileForUser(int $userId): SportProfile
    {
        return $this->requireProfile($userId);
    }

    private function conversationFor(SportSession $session): EventConversation
    {
        if ($session->sport_session_series_id !== null) {
            return EventConversation::query()->firstOrCreate([
                'sport_session_series_id' => $session->sport_session_series_id,
            ], ['sport_session_id' => $session->id]);
        }
        try {
            return EventConversation::query()->firstOrCreate(['sport_session_id' => $session->id]);
        } catch (QueryException $exception) {
            $conversation = EventConversation::query()->where('sport_session_id', $session->id)->first();
            if ($conversation === null) {
                throw $exception;
            }

            return $conversation;
        }
    }

    private function requireProfile(int $userId): SportProfile
    {
        return SportProfile::query()->where('user_id', $userId)->firstOr(fn () => throw new NotFoundHttpException);
    }

    private function assertMayAccess(SportProfile $profile, SportSession $session): void
    {
        if (($session->sport_session_series_id === null && $session->status->value !== 'open') || ($session->sport_session_series_id !== null && $session->series?->status !== 'active')) {
            throw new NotFoundHttpException;
        }
        $isHost = $session->creator_profile_id === $profile->id;
        $participantIds = $session->participationRecords()
            ->whereIn('status', self::PARTICIPANT_STATUSES)->pluck('sport_profile_id')->all();
        $isEligibleParticipant = in_array($profile->id, $participantIds, true);
        if ($session->visibility === 'private' && ! $isHost && ! $isEligibleParticipant) {
            throw new NotFoundHttpException;
        }
        $counterparts = $session->visibility === 'private'
            ? array_values(array_unique([...$participantIds, $session->creator_profile_id]))
            : [$session->creator_profile_id];
        foreach ($counterparts as $counterpartId) {
            if ($counterpartId !== $profile->id && $this->profilesAreBlocked($profile->id, $counterpartId)) {
                throw new NotFoundHttpException;
            }
        }
        $conversationId = EventConversation::query()
            ->when($session->sport_session_series_id !== null,
                fn ($query) => $query->where('sport_session_series_id', $session->sport_session_series_id),
                fn ($query) => $query->where('sport_session_id', $session->id))
            ->value('id');
        if ($conversationId !== null && EventConversationSanction::query()->where([
            'event_conversation_id' => $conversationId, 'sport_profile_id' => $profile->id, 'type' => 'ban',
        ])->exists()) {
            throw new NotFoundHttpException;
        }
    }

    private function assertWritable(SportProfile $profile, EventConversation $conversation): void
    {
        if ($conversation->status === 'archived') abort(403, 'This conversation is read-only.');
        $sanctions = EventConversationSanction::query()->where('event_conversation_id', $conversation->id)->where('sport_profile_id', $profile->id)
            ->where(fn ($query) => $query->where('type', 'ban')->orWhere(fn ($q) => $q->where('type', 'mute_profile')->where(fn ($until) => $until->whereNull('expires_at')->orWhere('expires_at', '>', now()))))->exists();
        if ($sanctions) abort(403, 'This profile cannot post in this conversation.');
    }

    private function assertHost(SportProfile $profile, SportSession $session): void
    {
        if ($session->creator_profile_id !== $profile->id) abort(403, 'Only the session host can moderate this conversation.');
    }

    private function audit(EventConversation $conversation, ?int $actorId, ?int $targetId, string $action, ?string $reason, ?array $before, ?array $after): void
    {
        EventConversationAudit::query()->create(['event_conversation_id' => $conversation->id, 'actor_profile_id' => $actorId, 'target_profile_id' => $targetId, 'action' => $action, 'reason' => $reason, 'before' => $before, 'after' => $after]);
    }

    private function profilesAreBlocked(int $firstProfileId, int $secondProfileId): bool
    {
        return Connection::query()->where('type', 'block')->where('status', 'blocked')
            ->where(fn ($query) => $query->where(function ($q) use ($firstProfileId, $secondProfileId) {
                $q->where('requester_profile_id', $firstProfileId)->where('target_profile_id', $secondProfileId);
            })->orWhere(function ($q) use ($firstProfileId, $secondProfileId) {
                $q->where('requester_profile_id', $secondProfileId)->where('target_profile_id', $firstProfileId);
            }))->exists();
    }

    private function rateLimit(string $action, int $profileId, int $sessionId, int $maxAttempts, int $seconds): void
    {
        $key = "event-conversation:$action:$profileId:$sessionId";
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new HttpResponseException(response()->json(['success' => false, 'message' => 'Muitas tentativas. Tente novamente em instantes.'], 429));
        }
        RateLimiter::hit($key, $seconds);
    }
}
