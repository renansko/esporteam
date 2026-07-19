<?php

namespace App\Events;

use App\Http\Resources\EventMessageResource;
use App\Models\EventMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class EventConversationSocialStateChanged implements ShouldBroadcast
{
    use Dispatchable;

    public string $queue = 'broadcasts';

    public function __construct(
        public int $conversationId,
        public string $kind,
        public ?EventMessage $message = null,
        public array $state = [],
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('event-conversations.'.$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'social.updated';
    }

    public function broadcastWith(): array
    {
        return $this->kind === 'message'
            ? ['kind' => 'message', 'message' => (new EventMessageResource($this->message))->resolve()]
            : ['kind' => $this->kind, ...$this->state];
    }
}
