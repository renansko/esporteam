<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Connection extends Model
{
    protected $fillable = [
        'requester_profile_id',
        'target_profile_id',
        'profile_low_id',
        'profile_high_id',
        'type',
        'status',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'requester_profile_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'target_profile_id');
    }
}
