<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportSessionSeriesFollower extends Model
{
    protected $fillable = ['sport_session_series_id', 'sport_profile_id'];

    public function series(): BelongsTo
    {
        return $this->belongsTo(SportSessionSeries::class, 'sport_session_series_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'sport_profile_id');
    }
}
