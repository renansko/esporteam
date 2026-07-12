<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Internal, sanitized operational trace for Bio Assistida provider calls.
 *
 * @wiki app/brain/entities/AiAuditEvent.md
 */
class AiAuditEvent extends Model
{
    protected $fillable = [
        'sport_profile_id',
        'operation',
        'outcome',
        'idempotency_key',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'sport_profile_id');
    }
}
