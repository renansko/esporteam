<?php

namespace App\Models;

use App\Enums\SessionParticipantStatus;
use App\Enums\SportSessionEntryMode;
use App\Enums\SportSessionStatus;
use App\Enums\SportSessionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @wiki app/brain/entities/SportSession.md
 */
class SportSession extends Model
{
    protected $fillable = [
        'creator_profile_id',
        'sport_id',
        'title',
        'description',
        'rules',
        'equipment',
        'type',
        'starts_at',
        'ends_at',
        'timezone',
        'location_label',
        'location_label_public',
        'meeting_point_label',
        'city',
        'region',
        'latitude_approx',
        'longitude_approx',
        'latitude_exact',
        'longitude_exact',
        'publication_key',
        'capacity',
        'requires_approval',
        'entry_mode',
        'min_level',
        'max_level',
        'visibility',
        'status',
    ];

    protected $casts = [
        'type' => SportSessionType::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'latitude_approx' => 'float',
        'longitude_approx' => 'float',
        'latitude_exact' => 'float',
        'longitude_exact' => 'float',
        'capacity' => 'integer',
        'requires_approval' => 'boolean',
        'entry_mode' => SportSessionEntryMode::class,
        'status' => SportSessionStatus::class,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'creator_profile_id');
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function participationRecords(): HasMany
    {
        return $this->hasMany(SessionParticipant::class)->orderBy('id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(SportProfile::class, 'session_participants', 'sport_session_id', 'sport_profile_id')
            ->using(SessionParticipant::class)
            ->wherePivotIn('status', SessionParticipantStatus::activeValues())
            ->withPivot(['status'])
            ->withTimestamps();
    }
}
