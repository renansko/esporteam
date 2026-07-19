<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A durable weekly rule. Discovery only ever receives its materialized sessions.
 *
 * @wiki app/brain/entities/SportSessionSeries.md
 */
class SportSessionSeries extends Model
{
    protected $fillable = [
        'creator_profile_id', 'sport_id', 'title', 'description', 'rules', 'equipment', 'type',
        'starts_on', 'starts_at_local', 'duration_minutes', 'timezone', 'interval_weeks', 'weekdays',
        'ends_type', 'ends_on', 'occurrence_count', 'location_label_public', 'meeting_point_label',
        'city', 'region', 'latitude_approx', 'longitude_approx', 'latitude_exact', 'longitude_exact',
        'capacity', 'requires_approval', 'entry_mode', 'min_level', 'max_level', 'visibility', 'status',
        'publication_key',
        'version',
    ];

    protected $casts = [
        'starts_on' => 'date', 'ends_on' => 'date', 'weekdays' => 'array',
        'duration_minutes' => 'integer', 'interval_weeks' => 'integer', 'occurrence_count' => 'integer',
        'latitude_approx' => 'float', 'longitude_approx' => 'float', 'latitude_exact' => 'float',
        'longitude_exact' => 'float', 'capacity' => 'integer', 'requires_approval' => 'boolean',
        'version' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'creator_profile_id');
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(SportSession::class, 'sport_session_series_id');
    }

    public function followers(): HasMany
    {
        return $this->hasMany(SportSessionSeriesFollower::class, 'sport_session_series_id');
    }
}
