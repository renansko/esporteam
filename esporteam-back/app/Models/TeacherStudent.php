<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TeacherStudent extends Pivot
{
    protected $table = 'teacher_students';

    protected $fillable = [
        'teacher_profile_id',
        'student_profile_id',
        'status',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
}
