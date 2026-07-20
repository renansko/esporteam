<?php

namespace App\Contracts;

use App\Models\PushSubscription;

interface ConversationPushAdapter
{
    /** @return 'sent'|'invalid'|'retry' */
    public function send(PushSubscription $subscription, array $payload): string;
}
