<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @wiki app/brain/entities/EventMessage.md
 */
class EventMessage extends Model
{
    protected $fillable = ['event_conversation_id', 'reply_to_event_message_id', 'author_profile_id', 'client_message_id', 'body'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(EventConversation::class, 'event_conversation_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'author_profile_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_event_message_id');
    }

    public function mentions(): HasMany
    {
        return $this->hasMany(EventMessageMention::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(EventMessageReaction::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(EventMessageMedia::class)->orderBy('position');
    }
}
