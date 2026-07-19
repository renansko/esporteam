<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\EventConversation;
use App\Services\EventConversationService;

/**
 * Canal privado de eventos de clustering por workspace.
 * Autorização: user precisa ter `workspace_id` igual ao do canal.
 */
Broadcast::channel('workspaces.{workspaceId}.clustering', function ($user, int $workspaceId) {
    return isset($user->workspace_id) && (int) $user->workspace_id === $workspaceId;
});

Broadcast::channel('event-conversations.{conversationId}', function ($user, int $conversationId) {
    if (! config('features.event_social_chat', false) || ! ($user->is_adult ?? false)) return false;
    $conversation = EventConversation::query()->find($conversationId);
    if ($conversation === null) return false;
    return app(EventConversationService::class)->mayAccessUser((int) $user->id, $conversation->session);
});
