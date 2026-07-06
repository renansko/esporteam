<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TeacherProfile extends Model
{
    protected $fillable = [
        'sport_profile_id',
        'headline',
        'credentials',
        'hourly_price_cents',
        'service_radius_km',
        'verified_at',
    ];

    protected $casts = [
        'hourly_price_cents' => 'integer',
        'service_radius_km' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(SportProfile::class, 'sport_profile_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(SportProfile::class, 'teacher_students', 'teacher_profile_id', 'student_profile_id')
            ->using(TeacherStudent::class)
            ->withPivot(['status', 'started_at', 'ended_at'])
            ->withTimestamps();
    }
}
