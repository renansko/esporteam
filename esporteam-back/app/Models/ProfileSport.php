<?php

namespace App\Models;

use App\Enums\SportLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileSport extends Model
{
    protected $fillable = [
        'sport_profile_id',
        'sport_id',
        'level',
        'goals',
        'preferred_positions',
        'is_primary',
    ];

    protected $casts = [
        'goals'      => 'array',
        'is_primary' => 'boolean',
        'level'      => SportLevel::class,
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'sport_profile_id');
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }
}
