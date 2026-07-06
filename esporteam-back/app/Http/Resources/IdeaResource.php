<?php

namespace App\Http\Resources;

use App\Models\Idea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @wiki app/brain/resources/IdeaResource.md
 *
 * @mixin Idea
 */
class IdeaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'source'          => $this->source?->value,
            'title'           => $this->title,
            'description'     => $this->description,
            'author_email'    => $this->author_email,
            'created_at'      => $this->created_at?->toISOString(),
            'clustered'       => $this->roadmap_item_id !== null,
            'roadmap_item_id' => $this->roadmap_item_id,
        ];
    }
}
