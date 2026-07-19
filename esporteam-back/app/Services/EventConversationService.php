<?php

namespace App\Services;

use App\Events\EventConversationMessagePosted;
use App\Models\Connection;
use App\Models\EventConversation;
use App\Models\EventMessage;
use App\Models\SportProfile;
use App\Models\SportSession;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
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
            ->with('author')
            ->where('event_conversation_id', $conversation->id)
            ->when($cursor !== null, fn ($query) => $query->where('id', '>', $cursor))
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();
        $hasMore = $messages->count() > $limit;
        $messages = $messages->take($limit)->values();

        return [
            'conversation' => ['id' => $conversation->id, 'session_id' => $session->id, 'status' => $conversation->status],
            'messages' => $messages,
            'next_cursor' => $hasMore ? (string) $messages->last()->id : null,
        ];
    }

    /** @wiki app/brain/functions/EventConversationService.md#postMessage */
    public function postMessage(int $userId, SportSession $session, string $body, string $clientMessageId): EventMessage
    {
        $profile = $this->requireProfile($userId);
        $this->assertMayAccess($profile, $session);
        $this->rateLimit('write', $profile->id, $session->id, 20, 60);
        $body = trim(strip_tags($body));
        if ($body === '') {
            throw ValidationException::withMessages(['body' => 'A mensagem precisa conter texto.']);
        }

        return DB::transaction(function () use ($profile, $session, $body, $clientMessageId): EventMessage {
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
                    'author_profile_id' => $profile->id,
                    'client_message_id' => $clientMessageId,
                    'body' => $body,
                ])->load('author');
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

    public function mayAccessUser(int $userId, SportSession $session): bool
    {
        $profile = SportProfile::query()->where('user_id', $userId)->first();
        if ($profile === null) return false;
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
            if ($conversation === null) throw $exception;
            return $conversation;
        }
    }

    private function requireProfile(int $userId): SportProfile
    {
        return SportProfile::query()->where('user_id', $userId)->firstOr(fn () => throw new NotFoundHttpException());
    }

    private function assertMayAccess(SportProfile $profile, SportSession $session): void
    {
        if ($session->sport_session_series_id !== null || $session->status->value !== 'open') {
            throw new NotFoundHttpException();
        }
        $isHost = $session->creator_profile_id === $profile->id;
        $participantIds = $session->participationRecords()
            ->whereIn('status', self::PARTICIPANT_STATUSES)->pluck('sport_profile_id')->all();
        $isEligibleParticipant = in_array($profile->id, $participantIds, true);
        if ($session->visibility === 'private' && ! $isHost && ! $isEligibleParticipant) {
            throw new NotFoundHttpException();
        }
        $counterparts = $session->visibility === 'private'
            ? array_values(array_unique([...$participantIds, $session->creator_profile_id]))
            : [$session->creator_profile_id];
        foreach ($counterparts as $counterpartId) {
            if ($counterpartId !== $profile->id && $this->profilesAreBlocked($profile->id, $counterpartId)) {
                throw new NotFoundHttpException();
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
