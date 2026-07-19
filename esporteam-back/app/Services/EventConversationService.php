<?php

namespace App\Services;

use App\Events\EventConversationMessagePosted;
use App\Events\EventConversationSocialStateChanged;
use App\Models\Connection;
use App\Models\EventConversation;
use App\Models\EventConversationMute;
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
            ->with(['author', 'replyTo.author', 'mentions.profile', 'reactions'])
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
    public function postMessage(int $userId, SportSession $session, string $body, string $clientMessageId, ?int $replyToMessageId = null): EventMessage
    {
        $profile = $this->requireProfile($userId);
        $this->assertMayAccess($profile, $session);
        $this->rateLimit('write', $profile->id, $session->id, 20, 60);
        $body = trim(strip_tags($body));
        if ($body === '') {
            throw ValidationException::withMessages(['body' => 'A mensagem precisa conter texto.']);
        }

        return DB::transaction(function () use ($profile, $session, $body, $clientMessageId, $replyToMessageId): EventMessage {
            $conversation = $this->conversationFor($session);
            $existing = EventMessage::query()
                ->with('author')
                ->where('event_conversation_id', $conversation->id)
                ->where('author_profile_id', $profile->id)
                ->where('client_message_id', $clientMessageId)
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            try {
                $message = EventMessage::query()->create([
                    'event_conversation_id' => $conversation->id,
                    'reply_to_event_message_id' => $replyToMessageId,
                    'author_profile_id' => $profile->id,
                    'client_message_id' => $clientMessageId,
                    'body' => $body,
                ])->load(['author', 'replyTo.author', 'mentions.profile', 'reactions']);
            } catch (QueryException $exception) {
                $message = EventMessage::query()->with('author')
                    ->where('event_conversation_id', $conversation->id)
                    ->where('author_profile_id', $profile->id)
                    ->where('client_message_id', $clientMessageId)->first();
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

        return match ($action) {
            'reply' => ['message' => $this->reply($profile, $session, $conversation, $command)],
            'mention' => $this->mention($profile, $session, $conversation, $command),
            'reaction' => $this->react($profile, $conversation, $command),
            'read' => $this->read($profile, $conversation, $command),
            'mute' => $this->mute($profile, $conversation, $command),
            'typing' => $this->typing($profile, $conversation, $command),
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

    private function conversationFor(SportSession $session): EventConversation
    {
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
        if ($session->sport_session_series_id !== null || $session->status->value !== 'open') {
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
