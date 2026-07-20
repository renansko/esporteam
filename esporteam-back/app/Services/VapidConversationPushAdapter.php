<?php

namespace App\Services;

use App\Contracts\ConversationPushAdapter;
use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class VapidConversationPushAdapter implements ConversationPushAdapter
{
    public function send(PushSubscription $subscription, array $payload): string
    {
        $auth = config('services.webpush');
        if (! $auth['public_key'] || ! $auth['private_key'] || ! $auth['subject']) {
            return 'retry';
        }

        $webPush = new WebPush(['VAPID' => ['subject' => $auth['subject'], 'publicKey' => $auth['public_key'], 'privateKey' => $auth['private_key']]]);
        $report = $webPush->sendOneNotification(Subscription::create([
            'endpoint' => $subscription->endpoint,
            'publicKey' => $subscription->keys['p256dh'] ?? '',
            'authToken' => $subscription->keys['auth'] ?? '',
        ]), json_encode($payload, JSON_THROW_ON_ERROR));

        if ($report->isSuccess()) return 'sent';
        $status = $report->getResponse()?->getStatusCode();
        return in_array($status, [404, 410], true) ? 'invalid' : 'retry';
    }
}
