<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMessageMention extends Model
{
    protected $fillable = ['event_message_id', 'mentioned_profile_id'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(EventMessage::class, 'event_message_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'mentioned_profile_id');
    }
}
