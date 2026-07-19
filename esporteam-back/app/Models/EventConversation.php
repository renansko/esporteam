<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @wiki app/brain/entities/EventConversation.md
 */
class EventConversation extends Model
{
    protected $fillable = ['sport_session_id', 'status'];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SportSession::class, 'sport_session_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EventMessage::class)->orderBy('id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(EventConversationRead::class);
    }
}
