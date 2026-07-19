<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** @wiki app/brain/entities/ConversationMedia.md */
class ConversationMedia extends Model
{
    protected $fillable = ['event_conversation_id', 'author_profile_id', 'upload_id', 'upload_key', 'safe_key', 'thumbnail_key', 'declared_mime', 'detected_mime', 'byte_size', 'status', 'rejection_code', 'upload_expires_at', 'queued_at', 'processed_at'];

    protected function casts(): array
    {
        return ['upload_expires_at' => 'datetime', 'queued_at' => 'datetime', 'processed_at' => 'datetime'];
    }

    public function conversation(): BelongsTo { return $this->belongsTo(EventConversation::class, 'event_conversation_id'); }
    public function author(): BelongsTo { return $this->belongsTo(SportProfile::class, 'author_profile_id'); }
    public function messageLink(): HasOne { return $this->hasOne(EventMessageMedia::class); }
}
