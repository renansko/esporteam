<?php

use App\Models\Connection;
use App\Models\EventMessage;
use App\Models\SportProfile;
use App\Models\SportSession;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    config()->set('features.event_social_chat', true);
});

function conversationSession(SportProfile $host, string $visibility = 'public'): SportSession
{
    return SportSession::query()->create([
        'creator_profile_id' => $host->id,
        'title' => 'Volei no parque',
        'type' => 'partida',
        'starts_at' => now()->addDay(),
        'visibility' => $visibility,
        'status' => 'open',
    ]);
}

it('opens a canonical public conversation and posts a sanitized idempotent message', function () {
    Event::fake();
    $host = SportProfile::query()->create(['user_id' => 2901, 'display_name' => 'Lia']);
    $guest = SportProfile::query()->create(['user_id' => 2902, 'display_name' => 'Rui']);
    $session = conversationSession($host);
    $messageId = 'd17fc0ee-39fb-4ca6-94cf-a06d1448595d';

    actingAsWorkspace(1, ['id' => $guest->user_id, 'is_adult' => true])
        ->getJson("/api/sessions/{$session->id}/conversation")
        ->assertOk()->assertJsonPath('data.messages', []);

    $first = actingAsWorkspace(1, ['id' => $guest->user_id, 'is_adult' => true])
        ->postJson("/api/sessions/{$session->id}/conversation/messages", ['body' => 'Oi <strong>time</strong>', 'client_message_id' => $messageId])
        ->assertCreated()->assertJsonPath('data.body', 'Oi time')->json('data.id');

    actingAsWorkspace(1, ['id' => $guest->user_id, 'is_adult' => true])
        ->postJson("/api/sessions/{$session->id}/conversation/messages", ['body' => 'tentativa diferente', 'client_message_id' => $messageId])
        ->assertCreated()->assertJsonPath('data.id', $first);

    expect(EventMessage::query()->count())->toBe(1);
});

it('uses a stable cursor and restricts private conversations and blocked profiles', function () {
    $host = SportProfile::query()->create(['user_id' => 2911, 'display_name' => 'Nina']);
    $guest = SportProfile::query()->create(['user_id' => 2912, 'display_name' => 'Caio']);
    $outsider = SportProfile::query()->create(['user_id' => 2913, 'display_name' => 'Sol']);
    $private = conversationSession($host, 'private');
    $private->participants()->attach($guest->id, ['status' => 'invited']);

    actingAsWorkspace(1, ['id' => $outsider->user_id, 'is_adult' => true])
        ->getJson("/api/sessions/{$private->id}/conversation")->assertNotFound();

    $firstId = '1c9e7c17-67bd-4d01-91b2-35fdb93196db';
    $secondId = 'b9f8a9f5-816d-4b39-81ad-fb3da2a1ab06';
    actingAsWorkspace(1, ['id' => $guest->user_id, 'is_adult' => true])
        ->postJson("/api/sessions/{$private->id}/conversation/messages", ['body' => 'primeira', 'client_message_id' => $firstId])->assertCreated();
    actingAsWorkspace(1, ['id' => $guest->user_id, 'is_adult' => true])
        ->postJson("/api/sessions/{$private->id}/conversation/messages", ['body' => 'segunda', 'client_message_id' => $secondId])->assertCreated();

    $firstCursor = EventMessage::query()->orderBy('id')->value('id');
    actingAsWorkspace(1, ['id' => $guest->user_id, 'is_adult' => true])
        ->getJson("/api/sessions/{$private->id}/conversation?cursor={$firstCursor}")
        ->assertOk()->assertJsonCount(1, 'data.messages')->assertJsonPath('data.messages.0.body', 'segunda');

    Connection::query()->create([
        'requester_profile_id' => $host->id, 'target_profile_id' => $guest->id,
        'profile_low_id' => min($host->id, $guest->id), 'profile_high_id' => max($host->id, $guest->id),
        'type' => 'block', 'status' => 'blocked',
    ]);
    actingAsWorkspace(1, ['id' => $guest->user_id, 'is_adult' => true])
        ->getJson("/api/sessions/{$private->id}/conversation")->assertNotFound();
});

it('does not expose chat routes while the feature is disabled', function () {
    config()->set('features.event_social_chat', false);
    $host = SportProfile::query()->create(['user_id' => 2921, 'display_name' => 'Iris']);
    $session = conversationSession($host);
    actingAsWorkspace(1, ['id' => $host->user_id, 'is_adult' => true])
        ->getJson("/api/sessions/{$session->id}/conversation")->assertNotFound();
});

it('applies replies, mentions, reactions, monotonic reads and mute through one social action endpoint', function () {
    $host = SportProfile::query()->create(['user_id' => 2931, 'display_name' => 'Bia']);
    $guest = SportProfile::query()->create(['user_id' => 2932, 'display_name' => 'Davi']);
    $session = conversationSession($host, 'private');
    $session->participants()->attach($guest->id, ['status' => 'joined']);
    $headers = ['id' => $guest->user_id, 'is_adult' => true];
    $messageId = 'e1fd5963-88a9-46d8-88d7-f8a5f41aef66';
    $message = actingAsWorkspace(1, $headers)->postJson("/api/sessions/{$session->id}/conversation/messages", ['body' => 'Vamos?', 'client_message_id' => $messageId])->assertCreated()->json('data');

    actingAsWorkspace(1, $headers)->postJson("/api/sessions/{$session->id}/conversation/actions", ['action' => 'mention', 'message_id' => $message['id'], 'mentioned_profile_id' => $host->id])
        ->assertOk()->assertJsonPath('data.message.mentions.0.id', $host->id);
    actingAsWorkspace(1, $headers)->postJson("/api/sessions/{$session->id}/conversation/actions", ['action' => 'reaction', 'message_id' => $message['id'], 'emoji' => '👍', 'active' => true])
        ->assertOk()->assertJsonPath('data.message.reactions.0.count', 1);
    actingAsWorkspace(1, $headers)->postJson("/api/sessions/{$session->id}/conversation/actions", ['action' => 'read', 'cursor' => $message['id']])->assertOk();
    actingAsWorkspace(1, $headers)->postJson("/api/sessions/{$session->id}/conversation/actions", ['action' => 'read', 'cursor' => 0])
        ->assertOk()->assertJsonPath('data.cursor', $message['id']);
    actingAsWorkspace(1, $headers)->postJson("/api/sessions/{$session->id}/conversation/actions", ['action' => 'mute', 'muted' => true])
        ->assertOk()->assertJsonPath('data.muted', true);
    actingAsWorkspace(1, $headers)->postJson("/api/sessions/{$session->id}/conversation/actions", [
        'action' => 'reply', 'message_id' => $message['id'], 'body' => 'Sim!', 'client_message_id' => '6ed75e52-f4ec-4e1f-b4b7-82d007ca1a4e',
    ])->assertOk()->assertJsonPath('data.message.reply_to.id', $message['id']);
});
