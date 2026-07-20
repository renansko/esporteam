<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventConversationAudit extends Model
{
    protected $fillable = ['event_conversation_id', 'actor_profile_id', 'target_profile_id', 'action', 'reason', 'before', 'after'];

    protected $casts = ['before' => 'array', 'after' => 'array'];
}
