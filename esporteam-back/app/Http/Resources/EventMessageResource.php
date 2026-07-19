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
            'author' => [
                'id' => $this->author?->id,
                'display_name' => $this->author?->display_name,
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
