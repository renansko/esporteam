<?php

namespace App\Jobs;

use App\Contracts\ConversationPushAdapter;
use App\Models\EventMessage;
use App\Models\PushDelivery;
use App\Services\NotificationActivity;
use App\Services\NotificationPolicy;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeliverConversationPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public array $backoff = [10, 30, 120, 600];

    public function __construct(public readonly int $messageId, public readonly string $activityType, public readonly ?int $recipientProfileId = null) {}

    public function handle(NotificationPolicy $policy, ConversationPushAdapter $adapter): void
    {
        $message = EventMessage::query()->find($this->messageId);
        if ($message === null) return;
        foreach ($policy->decide(new NotificationActivity($this->activityType, $message, $this->recipientProfileId)) as $delivery) {
            $key = hash('sha256', implode(':', [$this->activityType, $message->id, $delivery['profile_id']]));
            $record = PushDelivery::query()->firstOrCreate([
                'push_subscription_id' => $delivery['subscription']->id, 'idempotency_key' => $key,
            ], [
                'recipient_profile_id' => $delivery['profile_id'], 'event_conversation_id' => $message->event_conversation_id,
                'event_message_id' => $message->id, 'activity_type' => $this->activityType,
            ]);
            if ($record->status === 'sent' || $record->status === 'invalid') continue;

            $record->increment('attempts');
            $result = $adapter->send($delivery['subscription'], $this->payload($message));
            if ($result === 'sent') $record->update(['status' => 'sent', 'sent_at' => now()]);
            elseif ($result === 'invalid') {
                $record->update(['status' => 'invalid', 'failure_code' => 'subscription_gone']);
                $delivery['subscription']->update(['active' => false]);
            }
            else {
                $record->update(['status' => 'retry', 'failure_code' => 'transient_failure']);
                throw new \RuntimeException('Push delivery failed transiently.');
            }
        }
    }

    private function payload(EventMessage $message): array
    {
        $sessionId = $message->conversation()->value('sport_session_id');
        return ['title' => $message->kind === 'announcement' ? 'Novo anúncio da Sessão Esportiva' : 'Nova atividade na conversa', 'body' => 'Abra a conversa para ver a novidade.', 'data' => ['conversation_id' => $message->event_conversation_id, 'message_id' => $message->id, 'session_id' => $sessionId, 'url' => '/sessions/'.$sessionId]];
    }
}
