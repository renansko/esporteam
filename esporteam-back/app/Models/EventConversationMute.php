<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventConversationMute extends Model
{
    protected $fillable = ['event_conversation_id', 'sport_profile_id'];
}
