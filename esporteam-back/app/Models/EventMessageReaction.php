<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventMessageReaction extends Model
{
    protected $fillable = ['event_message_id', 'sport_profile_id', 'emoji'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(EventMessage::class, 'event_message_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'sport_profile_id');
    }
}
