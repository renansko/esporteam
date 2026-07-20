<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushDelivery extends Model
{
    protected $fillable = ['push_subscription_id', 'recipient_profile_id', 'event_conversation_id', 'event_message_id', 'activity_type', 'idempotency_key', 'status', 'attempts', 'failure_code', 'sent_at'];
    protected $casts = ['sent_at' => 'datetime'];
}
