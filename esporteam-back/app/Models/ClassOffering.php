<?php

namespace App\Models;

use App\Enums\ClassOfferingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassOffering extends Model
{
    protected $fillable = [
        'teacher_profile_id',
        'sport_id',
        'title',
        'description',
        'price_cents',
        'starts_at',
        'recurrence',
        'location_label',
        'city',
        'region',
        'latitude_approx',
        'longitude_approx',
        'capacity',
        'status',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'starts_at' => 'datetime',
        'latitude_approx' => 'float',
        'longitude_approx' => 'float',
        'capacity' => 'integer',
        'status' => ClassOfferingStatus::class,
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function interests(): HasMany
    {
        return $this->hasMany(ClassInterest::class);
    }

    public function interestedProfiles(): BelongsToMany
    {
        return $this->belongsToMany(SportProfile::class, 'class_interests', 'class_offering_id', 'sport_profile_id')
            ->using(ClassInterest::class)
            ->withPivot(['status'])
            ->withTimestamps();
    }
}
