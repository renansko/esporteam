<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushPreference extends Model
{
    protected $fillable = ['user_id', 'enabled'];
    protected $casts = ['enabled' => 'boolean'];
}
