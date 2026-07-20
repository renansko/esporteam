<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status ?? 'published';

        return [
            'id' => $this->id,
            'cursor' => (string) $this->id,
            'client_message_id' => $this->client_message_id,
            'body' => $status === 'published' ? $this->body : null,
            'status' => $status,
            'kind' => $this->kind ?? 'message',
            'tombstone' => $status !== 'published',
            'reply_to' => $this->whenLoaded('replyTo', fn () => $this->replyTo === null ? null : [
                'id' => $this->replyTo->id,
                'body' => ($this->replyTo->status ?? 'published') === 'published' ? $this->replyTo->body : null,
                'status' => $this->replyTo->status ?? 'published',
                'author' => ['id' => $this->replyTo->author?->id, 'display_name' => $this->replyTo->author?->display_name],
            ]),
            'mentions' => $this->whenLoaded('mentions', fn () => $this->mentions->map(fn ($mention) => [
                'id' => $mention->profile?->id, 'display_name' => $mention->profile?->display_name,
            ])->values()),
            'reactions' => $this->whenLoaded('reactions', fn () => $this->reactions
                ->groupBy('emoji')->map(fn ($items, $emoji) => ['emoji' => $emoji, 'count' => $items->count(), 'reacted' => $items->contains('sport_profile_id', $request->attributes->get('sport_profile_id'))])->values()),
            'seen_by_count' => $this->when($this->getAttribute('seen_by_count') !== null, $this->getAttribute('seen_by_count')),
            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn ($link) => [
                'id' => $link->media?->id,
                'status' => $link->media?->status,
            ])->values()),
            'author' => [
                'id' => $this->author?->id,
                'display_name' => $this->author?->display_name,
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
