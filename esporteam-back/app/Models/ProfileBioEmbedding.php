<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @wiki app/brain/entities/ProfileBioEmbedding.md
 */
class ProfileBioEmbedding extends Model
{
    protected $fillable = [
        'sport_profile_id',
        'status',
        'model',
        'source_hash',
        'embedded_at',
        'failure_code',
        'metadata',
        'embedding',
    ];

    protected $casts = [
        'embedded_at' => 'datetime',
        'metadata' => 'array',
        'embedding' => 'array',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'sport_profile_id');
    }
}
