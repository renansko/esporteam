<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SportGroup extends Model
{
    protected $fillable = [
        'creator_profile_id',
        'sport_id',
        'name',
        'description',
        'visibility',
        'capacity',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'creator_profile_id');
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(SportGroupMember::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(SportProfile::class, 'sport_group_members', 'sport_group_id', 'sport_profile_id')
            ->using(SportGroupMember::class)
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }
}
