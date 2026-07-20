<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = ['user_id', 'device_id', 'endpoint', 'keys', 'active', 'last_seen_at'];
    protected $casts = ['keys' => 'array', 'active' => 'boolean', 'last_seen_at' => 'datetime'];
}
