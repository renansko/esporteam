<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventConversationSanction extends Model
{
    protected $fillable = ['event_conversation_id', 'sport_profile_id', 'imposed_by_profile_id', 'type', 'reason', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function conversation(): BelongsTo { return $this->belongsTo(EventConversation::class, 'event_conversation_id'); }
    public function profile(): BelongsTo { return $this->belongsTo(SportProfile::class, 'sport_profile_id'); }
}
