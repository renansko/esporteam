<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @wiki app/brain/entities/AvailabilityWindow.md
 */
class AvailabilityWindow extends Model
{
    protected $fillable = [
        'sport_profile_id',
        'weekday',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'weekday' => 'integer',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'sport_profile_id');
    }
}
