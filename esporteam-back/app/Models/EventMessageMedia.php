<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMessageMedia extends Model
{
    protected $fillable = ['event_message_id', 'conversation_media_id', 'position'];
    public function message(): BelongsTo { return $this->belongsTo(EventMessage::class, 'event_message_id'); }
    public function media(): BelongsTo { return $this->belongsTo(ConversationMedia::class, 'conversation_media_id'); }
}
