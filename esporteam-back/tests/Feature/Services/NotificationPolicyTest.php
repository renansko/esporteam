<?php

use App\Contracts\ConversationPushAdapter;
use App\Jobs\DeliverConversationPush;
use App\Models\EventConversation;
use App\Models\EventConversationMute;
use App\Models\EventMessage;
use App\Models\PushDelivery;
use App\Models\PushSubscription;
use App\Models\SportProfile;
use App\Models\SportSession;
use App\Services\FakeConversationPushAdapter;
use App\Services\NotificationPolicy;

beforeEach(fn () => config()->set('features.event_push_notifications', true));

function pushFixture(): array
{
    $host = SportProfile::query()->create(['user_id' => 8801, 'display_name' => 'Host']);
    $guest = SportProfile::query()->create(['user_id' => 8802, 'display_name' => 'Guest']);
    $session = SportSession::query()->create(['creator_profile_id' => $host->id, 'title' => 'Sessão', 'type' => 'partida', 'starts_at' => now()->addDay(), 'status' => 'open']);
    $session->participants()->attach($guest->id, ['status' => 'joined']);
    $conversation = EventConversation::query()->create(['sport_session_id' => $session->id, 'status' => 'active']);
    $message = EventMessage::query()->create(['event_conversation_id' => $conversation->id, 'author_profile_id' => $host->id, 'client_message_id' => (string) Str::uuid(), 'body' => 'Resposta', 'kind' => 'message']);
    PushSubscription::query()->create(['user_id' => $guest->user_id, 'device_id' => 'device-1', 'endpoint' => 'https://push.test/1', 'keys' => ['p256dh' => 'p', 'auth' => 'a']]);
    return compact('host', 'guest', 'session', 'conversation', 'message');
}

it('allows only eligible activities and suppresses muted recipients', function () {
    $fixture = pushFixture();
    $message = $fixture['message'];
    $message->reply_to_event_message_id = $message->id;
    $message->save();

    expect(app(NotificationPolicy::class)->decide(new \App\Services\NotificationActivity('reply', $message, $fixture['guest']->id)))->toHaveCount(1)
        ->and(app(NotificationPolicy::class)->decide(new \App\Services\NotificationActivity('message', $message)))->toBeEmpty();

    EventConversationMute::query()->create(['event_conversation_id' => $fixture['conversation']->id, 'sport_profile_id' => $fixture['guest']->id]);
    expect(app(NotificationPolicy::class)->decide(new \App\Services\NotificationActivity('reply', $message, $fixture['guest']->id)))->toBeEmpty();
});

it('deduplicates delivery across job retries', function () {
    $fixture = pushFixture();
    $adapter = new FakeConversationPushAdapter();
    app()->instance(ConversationPushAdapter::class, $adapter);
    $job = new DeliverConversationPush($fixture['message']->id, 'mention', $fixture['guest']->id);

    $job->handle(app(NotificationPolicy::class), $adapter);
    $job->handle(app(NotificationPolicy::class), $adapter);

    expect(PushDelivery::query()->count())->toBe(1)->and($adapter->sent)->toHaveCount(1);
});
