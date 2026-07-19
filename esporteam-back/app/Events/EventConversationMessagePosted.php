<?php

namespace App\Events;

use App\Http\Resources\EventMessageResource;
use App\Models\EventMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventConversationMessagePosted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public string $queue = 'broadcasts';

    public function __construct(public EventMessage $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('event-conversations.'.$this->message->event_conversation_id)];
    }

    public function broadcastAs(): string
    {
        return 'message.posted';
    }

    public function broadcastWith(): array
    {
        return (new EventMessageResource($this->message->loadMissing('author')))->resolve();
    }
}
