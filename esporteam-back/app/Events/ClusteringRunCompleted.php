<?php

namespace App\Events;

use App\Models\ClusteringRun;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClusteringRunCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ClusteringRun $run) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("workspaces.{$this->run->workspace_id}.clustering")];
    }

    public function broadcastAs(): string
    {
        return 'clustering.run.completed';
    }

    /** @return array<string,mixed> */
    public function broadcastWith(): array
    {
        return [
            'run_id'         => $this->run->id,
            'status'         => $this->run->status?->value,
            'items_created'  => $this->run->items_created,
            'items_assigned' => $this->run->items_assigned,
            'fallback_used'  => (bool) $this->run->fallback_used,
            'completed_at'   => $this->run->completed_at?->toISOString(),
        ];
    }
}
