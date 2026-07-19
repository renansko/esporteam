<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventConversationRead extends Model
{
    protected $fillable = ['event_conversation_id', 'sport_profile_id', 'last_read_message_id'];
}
