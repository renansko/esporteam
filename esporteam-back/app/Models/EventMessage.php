<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @wiki app/brain/entities/EventMessage.md
 */
class EventMessage extends Model
{
    protected $fillable = ['event_conversation_id', 'author_profile_id', 'client_message_id', 'body'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(EventConversation::class, 'event_conversation_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'author_profile_id');
    }
}
