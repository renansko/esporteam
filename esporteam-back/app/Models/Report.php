<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'reporter_profile_id',
        'reported_profile_id',
        'reason',
        'details',
        'status',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'reporter_profile_id');
    }

    public function reported(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'reported_profile_id');
    }
}
