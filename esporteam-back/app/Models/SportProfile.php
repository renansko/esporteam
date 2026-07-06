<?php

namespace App\Models;

use App\Enums\ProfileVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SportProfile extends Model
{
    protected $fillable = [
        'user_id',
        'display_name',
        'bio',
        'city',
        'region',
        'latitude_approx',
        'longitude_approx',
        'visibility',
        'avatar_url',
    ];

    protected $casts = [
        'latitude_approx' => 'float',
        'longitude_approx' => 'float',
        'visibility' => ProfileVisibility::class,
    ];

    public function sports(): HasMany
    {
        return $this->hasMany(ProfileSport::class);
    }

    public function availabilityWindows(): HasMany
    {
        return $this->hasMany(AvailabilityWindow::class);
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(TeacherProfile::class, 'teacher_students', 'student_profile_id', 'teacher_profile_id')
            ->using(TeacherStudent::class)
            ->withPivot(['status', 'started_at', 'ended_at'])
            ->withTimestamps();
    }

    public function createdGroups(): HasMany
    {
        return $this->hasMany(SportGroup::class, 'creator_profile_id');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(SportGroup::class, 'sport_group_members', 'sport_profile_id', 'sport_group_id')
            ->using(SportGroupMember::class)
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    public function requestedConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'requester_profile_id');
    }

    public function receivedConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'target_profile_id');
    }
}
