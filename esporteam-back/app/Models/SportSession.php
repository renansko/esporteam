<?php

namespace App\Models;

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
        'type',
        'starts_at',
        'location_label',
        'city',
        'region',
        'latitude_approx',
        'longitude_approx',
        'capacity',
        'visibility',
        'status',
    ];

    protected $casts = [
        'type' => SportSessionType::class,
        'starts_at' => 'datetime',
        'latitude_approx' => 'float',
        'longitude_approx' => 'float',
        'capacity' => 'integer',
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
        return $this->hasMany(SessionParticipant::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(SportProfile::class, 'session_participants', 'sport_session_id', 'sport_profile_id')
            ->using(SessionParticipant::class)
            ->withPivot(['status'])
            ->withTimestamps();
    }
}
