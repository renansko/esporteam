<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SportGroupMember extends Pivot
{
    protected $table = 'sport_group_members';

    protected $fillable = [
        'sport_group_id',
        'sport_profile_id',
        'role',
        'status',
    ];
}
