<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * Canal privado de eventos de clustering por workspace.
 * Autorização: user precisa ter `workspace_id` igual ao do canal.
 */
Broadcast::channel('workspaces.{workspaceId}.clustering', function ($user, int $workspaceId) {
    return isset($user->workspace_id) && (int) $user->workspace_id === $workspaceId;
});
