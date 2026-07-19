<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cursor' => (string) $this->id,
            'client_message_id' => $this->client_message_id,
            'body' => $this->body,
            'reply_to' => $this->whenLoaded('replyTo', fn () => $this->replyTo === null ? null : [
                'id' => $this->replyTo->id,
                'body' => $this->replyTo->body,
                'author' => ['id' => $this->replyTo->author?->id, 'display_name' => $this->replyTo->author?->display_name],
            ]),
            'mentions' => $this->whenLoaded('mentions', fn () => $this->mentions->map(fn ($mention) => [
                'id' => $mention->profile?->id, 'display_name' => $mention->profile?->display_name,
            ])->values()),
            'reactions' => $this->whenLoaded('reactions', fn () => $this->reactions
                ->groupBy('emoji')->map(fn ($items, $emoji) => ['emoji' => $emoji, 'count' => $items->count(), 'reacted' => $items->contains('sport_profile_id', $request->attributes->get('sport_profile_id'))])->values()),
            'seen_by_count' => $this->when($this->getAttribute('seen_by_count') !== null, $this->getAttribute('seen_by_count')),
            'author' => [
                'id' => $this->author?->id,
                'display_name' => $this->author?->display_name,
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
