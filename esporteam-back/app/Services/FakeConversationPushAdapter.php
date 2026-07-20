<?php

namespace App\Services;

use App\Contracts\ConversationPushAdapter;
use App\Models\PushSubscription;

class FakeConversationPushAdapter implements ConversationPushAdapter
{
    public array $sent = [];
    public string $nextResult = 'sent';

    public function send(PushSubscription $subscription, array $payload): string
    {
        $this->sent[] = ['subscription_id' => $subscription->id, 'payload' => $payload];
        return $this->nextResult;
    }
}
