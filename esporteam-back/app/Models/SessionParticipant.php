<?php

namespace App\Models;

use App\Enums\SessionParticipantStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @wiki app/brain/entities/SessionParticipant.md
 */
class SessionParticipant extends Pivot
{
    protected $table = 'session_participants';

    protected $fillable = [
        'sport_session_id',
        'sport_profile_id',
        'status',
    ];

    protected $casts = [
        'status' => SessionParticipantStatus::class,
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SportSession::class, 'sport_session_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'sport_profile_id');
    }
}
