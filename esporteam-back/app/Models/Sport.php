<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sport extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function profileSports(): HasMany
    {
        return $this->hasMany(ProfileSport::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(SportSession::class);
    }
}
